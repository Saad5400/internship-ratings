<?php

use App\Models\User;
use Filament\Facades\Filament;

test('admin users can access the filament panel', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
});

test('non-admin users cannot access the filament panel', function () {
    $user = User::factory()->create();

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});

test('the admin panel login page is reachable', function () {
    $this->get('/admin/login')->assertOk();
});

test('the admin panel renders right-to-left', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('dir="rtl"', false);
});

test('non-admin users are denied entry to the admin panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

test('make-admin command grants admin access', function () {
    $user = User::factory()->create(['email' => 'grantee@example.com']);

    $this->artisan('app:make-admin', ['email' => 'grantee@example.com'])
        ->assertSuccessful();

    expect($user->fresh()->is_admin)->toBeTrue();
});

test('make-admin command revokes admin access with the revoke option', function () {
    $user = User::factory()->admin()->create(['email' => 'revokee@example.com']);

    $this->artisan('app:make-admin', ['email' => 'revokee@example.com', '--revoke' => true])
        ->assertSuccessful();

    expect($user->fresh()->is_admin)->toBeFalse();
});

test('make-admin command fails for an unknown email', function () {
    $this->artisan('app:make-admin', ['email' => 'missing@example.com'])
        ->assertFailed();
});
