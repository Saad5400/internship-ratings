<?php

use App\Models\User;

test('the admin login page is reachable', function () {
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

test('make-admin creates a missing user with admin access and a setup link', function () {
    $this->artisan('app:make-admin', ['email' => 'new-admin@example.com'])
        ->expectsOutputToContain('created and granted admin access')
        ->expectsOutputToContain('/admin/setup/')
        ->assertSuccessful();

    $user = User::where('email', 'new-admin@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->is_admin)->toBeTrue()
        ->and($user->password)->not->toBeEmpty();
});

test('make-admin with revoke still fails for an unknown email', function () {
    $this->artisan('app:make-admin', ['email' => 'missing@example.com', '--revoke' => true])
        ->assertFailed();
});

test('make-admin can print a fresh setup link for an existing user', function () {
    User::factory()->admin()->create(['email' => 'reset-me@example.com']);

    $this->artisan('app:make-admin', ['email' => 'reset-me@example.com', '--setup-link' => true])
        ->expectsOutputToContain('/admin/setup/')
        ->assertSuccessful();
});
