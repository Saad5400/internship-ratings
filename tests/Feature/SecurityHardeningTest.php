<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

/*
 * Admin access is re-checked on every Livewire request, not only on the page
 * load the route middleware sees.
 */
dataset('admin components', [
    'dashboard' => ['pages::admin.dashboard', []],
    'users' => ['pages::admin.users.index', []],
    'companies' => ['pages::admin.companies.index', []],
    'ratings' => ['pages::admin.ratings.index', []],
]);

test('admin components refuse a signed-in non-admin', function (string $component, array $params) {
    $this->actingAs(User::factory()->create());

    Livewire::test($component, $params)->assertForbidden();
})->with('admin components');

test('a revoked admin cannot keep acting through an admin page already open', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->create(['is_admin' => false]);

    $this->actingAs($admin);
    $page = Livewire::test('pages::admin.users.index');

    // Revoked while the page is open in their browser.
    $admin->forceFill(['is_admin' => false])->save();
    $this->actingAs($admin->fresh());

    $page->call('startEdit', $other->id)->assertForbidden();
});

test('the users table sort column cannot be set from the client', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test('pages::admin.users.index')->set('sortField', 'password');
})->throws(CannotUpdateLockedPropertyException::class);

test('the public pages keep their paging and reveal state server-side', function () {
    $company = Company::create(['name' => 'شركة', 'status' => 'approved']);

    expect(fn () => Livewire::test('pages::companies.index')->set('perPage', 100000))
        ->toThrow(CannotUpdateLockedPropertyException::class);

    expect(fn () => Livewire::test('pages::companies.show', ['company' => $company])->set('revealedContacts', [1, 2, 3]))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

/*
 * Setup links are single-use: setting the password retires the link, and a
 * snapshot taken while it was live can't be replayed afterwards.
 */
test('a setup link stops working once the password has been set through it', function () {
    $invitee = User::factory()->admin()->create();
    $link = $invitee->setupLink();

    $this->get($link)->assertOk();

    setupComponent($invitee)
        ->set('password', 'chosen-new-password')
        ->set('password_confirmation', 'chosen-new-password')
        ->call('save')
        ->assertHasNoErrors();

    auth()->logout();

    $this->get($link)->assertForbidden();
});

test('a setup page opened before the password changed cannot be submitted after', function () {
    $invitee = User::factory()->admin()->create();
    $page = setupComponent($invitee);

    // Someone else used the link (or the password was changed another way).
    $invitee->forceFill(['password' => 'owner-chosen-password'])->save();

    $page->set('password', 'attacker-password')
        ->set('password_confirmation', 'attacker-password')
        ->call('save')
        ->assertForbidden();

    expect(Hash::check('owner-chosen-password', $invitee->fresh()->password))->toBeTrue();
});

test('a setup link without the password fingerprint is refused', function () {
    $invitee = User::factory()->admin()->create();
    $link = preg_replace('/([?&])v=[^&]+&?/', '$1', $invitee->setupLink());

    $this->get($link)->assertForbidden();
});

/*
 * Company names come from anonymous submissions; JSON-LD sits inside a
 * <script> element, where HTML-escaping does not apply.
 */
test('a company name cannot break out of the JSON-LD script block', function () {
    $company = Company::create([
        'name' => 'Evil</script><script>alert(1)</script>',
        'status' => 'approved',
    ]);

    $this->get(route('companies.show', $company))
        ->assertOk()
        ->assertDontSee('</script><script>alert(1)', false)
        ->assertSee('Evil'.chr(92).'u003C/script'.chr(92).'u003E', false);

    $this->get(route('companies.index'))
        ->assertOk()
        ->assertDontSee('</script><script>alert(1)', false);
});

/*
 * The admin list sorts are whitelisted at query time: a direction set from the
 * client on a later update must never reach the raw ORDER BY.
 */
test('a tampered admin sort direction never reaches the raw ORDER BY', function (string $component, string $field) {
    $this->actingAs(User::factory()->admin()->create());
    Company::create(['name' => 'شركة', 'status' => 'approved']);

    Livewire::test($component)
        ->call('sortBy', $field)
        ->set('sortDirection', 'asc, (select x from no_such_table) --')
        ->set('sortField', 'no_such_column')
        ->assertOk();
})->with([
    'companies' => ['pages::admin.companies.index', 'approved_ratings_avg'],
    'ratings' => ['pages::admin.ratings.index', 'overall_rating'],
]);
