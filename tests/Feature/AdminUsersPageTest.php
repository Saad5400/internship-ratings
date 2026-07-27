<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

test('the users page renders for an admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('المستخدمون')
        ->assertSee('إضافة مشرف');
});

test('guests are redirected to login from the users page', function () {
    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
});

test('non-admins cannot access the users page', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('inviting without a manual password creates the user and a signed setup link', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.index')
        ->set('inviteName', 'مشرف جديد')
        ->set('inviteEmail', 'invitee@example.com')
        ->set('inviteIsAdmin', true)
        ->call('invite')
        ->assertHasNoErrors()
        ->assertSet('inviteLink', fn (string $link) => str_contains($link, '/admin/setup/'))
        ->assertDispatched('close-dialog', id: 'invite-user')
        ->assertDispatched('open-dialog', id: 'invite-link');

    $user = User::query()->where('email', 'invitee@example.com')->firstOrFail();

    expect($user->is_admin)->toBeTrue()
        ->and($user->password)->not->toBeNull()
        ->and($user->password)->not->toBe('')
        ->and(Hash::isHashed($user->password))->toBeTrue();
});

test('inviting with a manual password stores it hashed', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.index')
        ->set('inviteName', 'مستخدم يدوي')
        ->set('inviteEmail', 'manual@example.com')
        ->set('inviteIsAdmin', true)
        ->set('invitePassword', 'manual-secret-password')
        ->call('invite')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $user = User::query()->where('email', 'manual@example.com')->firstOrFail();

    expect($user->password)->not->toBe('manual-secret-password')
        ->and(Hash::check('manual-secret-password', $user->password))->toBeTrue();
});

test('inviting with a duplicate email fails validation on the email field', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'taken-invite@example.com']);

    Livewire::actingAs($admin)
        ->test('pages::admin.users.index')
        ->set('inviteName', 'مكرر')
        ->set('inviteEmail', 'taken-invite@example.com')
        ->call('invite')
        ->assertHasErrors(['inviteEmail' => 'unique']);

    expect(User::query()->where('email', 'taken-invite@example.com')->count())->toBe(1);
});

test('the setup link row action regenerates a signed link for an existing user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.index')
        ->call('generateSetupLink', $user->id)
        ->assertSet('inviteLink', fn (string $link) => str_contains($link, "/admin/setup/{$user->id}"))
        ->assertDispatched('open-dialog', id: 'invite-link');
});

test('editing with a blank password keeps the existing hash', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['password' => Hash::make('original-password')]);
    $originalHash = $user->fresh()->password;

    Livewire::actingAs($admin)
        ->test('pages::admin.users.index')
        ->call('startEdit', $user->id)
        ->set('editName', 'اسم محدث')
        ->set('editPassword', '')
        ->call('saveEdit')
        ->assertHasNoErrors()
        ->assertDispatched('close-dialog', id: 'edit-user');

    $user->refresh();

    expect($user->name)->toBe('اسم محدث')
        ->and($user->password)->toBe($originalHash)
        ->and(Hash::check('original-password', $user->password))->toBeTrue();
});

test('editing with a new password re-hashes it', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['password' => Hash::make('original-password')]);
    $originalHash = $user->fresh()->password;

    Livewire::actingAs($admin)
        ->test('pages::admin.users.index')
        ->call('startEdit', $user->id)
        ->set('editPassword', 'brand-new-password')
        ->call('saveEdit')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->password)->not->toBe($originalHash)
        ->and(Hash::check('brand-new-password', $user->password))->toBeTrue();
});

test('the last admin cannot be demoted from the users page', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.index')
        ->call('startEdit', $admin->id)
        ->set('editIsAdmin', false)
        ->call('saveEdit')
        ->assertHasErrors(['editIsAdmin']);

    expect($admin->fresh()->is_admin)->toBeTrue();
});

test('an admin can be demoted when another admin remains', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.index')
        ->call('startEdit', $target->id)
        ->set('editIsAdmin', false)
        ->call('saveEdit')
        ->assertHasNoErrors();

    expect($target->fresh()->is_admin)->toBeFalse();
});

test('an admin cannot delete their own account', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.users.index')
        ->call('deleteUser', $admin->id)
        ->assertDispatched('toast', type: 'danger');

    expect($admin->fresh())->not->toBeNull();
});

test('the last admin cannot be deleted', function () {
    $actingUser = User::factory()->create(['is_admin' => false]);
    $lastAdmin = User::factory()->admin()->create();

    Livewire::actingAs($actingUser)
        ->test('pages::admin.users.index')
        ->call('deleteUser', $lastAdmin->id)
        ->assertDispatched('toast', type: 'danger');

    expect($lastAdmin->fresh())->not->toBeNull();
});

test('a regular user can be deleted', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['is_admin' => false]);

    Livewire::actingAs($admin)
        ->test('pages::admin.users.index')
        ->call('deleteUser', $user->id)
        ->assertDispatched('toast', type: 'success');

    expect($user->fresh())->toBeNull();
});

test('a signed setup link renders and lets the invitee set a password and sign in', function () {
    $invitee = User::factory()->admin()->unverified()->create();

    $url = URL::temporarySignedRoute('admin.setup', now()->addDays(7), ['user' => $invitee->id]);

    $this->get($url)
        ->assertOk()
        ->assertSee('تعيين كلمة المرور')
        ->assertSee($invitee->email);

    Livewire::test('pages::admin.setup', ['user' => $invitee])
        ->set('password', 'chosen-new-password')
        ->set('password_confirmation', 'chosen-new-password')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.dashboard'));

    $invitee->refresh();

    expect(Hash::check('chosen-new-password', $invitee->password))->toBeTrue()
        ->and($invitee->email_verified_at)->not->toBeNull();

    $this->assertAuthenticatedAs($invitee);
});

test('the setup page rejects mismatched password confirmation', function () {
    $invitee = User::factory()->admin()->create();

    Livewire::test('pages::admin.setup', ['user' => $invitee])
        ->set('password', 'chosen-new-password')
        ->set('password_confirmation', 'different-password')
        ->call('save')
        ->assertHasErrors(['password' => 'confirmed']);
});

test('an unsigned setup URL returns 403', function () {
    $invitee = User::factory()->admin()->create();

    $this->get("/admin/setup/{$invitee->id}")->assertForbidden();
});

test('an expired setup URL returns 403', function () {
    $invitee = User::factory()->admin()->create();

    $url = URL::temporarySignedRoute('admin.setup', now()->addDays(7), ['user' => $invitee->id]);

    $this->travel(8)->days();

    $this->get($url)->assertForbidden();
});
