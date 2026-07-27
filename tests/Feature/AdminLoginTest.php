<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

test('an admin can log in and is redirected to the dashboard', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);

    Livewire::test('pages::admin.login')
        ->set('email', 'admin@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.dashboard'));

    expect(Auth::id())->toBe($admin->id);
});

test('login fails with wrong credentials', function () {
    User::factory()->admin()->create(['email' => 'admin@example.com']);

    Livewire::test('pages::admin.login')
        ->set('email', 'admin@example.com')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    expect(Auth::check())->toBeFalse();
});

test('non-admin users cannot log in even with valid credentials', function () {
    User::factory()->create(['email' => 'user@example.com']);

    Livewire::test('pages::admin.login')
        ->set('email', 'user@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors('email');

    expect(Auth::check())->toBeFalse();
});

test('guests are redirected from the admin panel to the login page', function () {
    $this->get('/admin')->assertRedirect(route('login'));
});

test('authenticated non-admins get a 403 from the admin panel', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertForbidden();
});

test('an admin can view the dashboard', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin')
        ->assertOk()
        ->assertSee('المراجعة')
        ->assertSee('dir="rtl"', false);
});

test('a logged-in admin visiting the login page is redirected to the dashboard', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/login')
        ->assertRedirect(route('admin.dashboard'));
});

test('logout ends the session and returns to the login page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/admin/logout')
        ->assertRedirect(route('login'));

    expect(Auth::check())->toBeFalse();
});
