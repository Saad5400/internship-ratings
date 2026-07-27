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

test('make-admin command fails for an unknown email', function () {
    $this->artisan('app:make-admin', ['email' => 'missing@example.com'])
        ->assertFailed();
});
