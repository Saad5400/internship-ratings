<?php

use App\Models\Company;
use App\Models\Rating;
use App\Models\User;
use Livewire\Livewire;

/**
 * @param  array<string, mixed>  $overrides
 */
function companiesPageRating(Company $company, array $overrides = []): Rating
{
    return Rating::create(array_merge([
        'company_id' => $company->id,
        'role_title' => 'مهندس برمجيات',
        'modality' => 'onsite',
        'rating_learning' => 5,
        'rating_mentorship' => 4,
        'rating_real_work' => 4,
        'rating_team_environment' => 3,
        'rating_organization' => 5,
        'status' => 'pending',
    ], $overrides));
}

/**
 * @param  array<string, mixed>  $overrides
 */
function companiesPageCompany(array $overrides = []): Company
{
    return Company::create(array_merge([
        'name' => 'جهة تدريبية',
        'type' => 'private',
        'status' => 'approved',
    ], $overrides));
}

test('the companies index renders for an admin only', function () {
    companiesPageCompany(['name' => 'جهة الفهرس']);

    $this->get(route('admin.companies.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('admin.companies.index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.companies.index'))
        ->assertOk()
        ->assertSee('الجهات')
        ->assertSee('جهة الفهرس');
});

test('search matches the normalized Arabic name', function () {
    $admin = User::factory()->admin()->create();
    companiesPageCompany(['name' => 'شركة أرامكو السعودية']);
    companiesPageCompany(['name' => 'جهة أخرى تماماً']);

    Livewire::actingAs($admin)
        ->test('pages::admin.companies.index')
        ->set('search', 'ارامكو')
        ->assertSee('شركة أرامكو السعودية')
        ->assertDontSee('جهة أخرى تماماً');
});

test('status tabs filter the list', function () {
    $admin = User::factory()->admin()->create();
    companiesPageCompany(['name' => 'جهة النفط', 'status' => 'approved']);
    companiesPageCompany(['name' => 'جهة الغاز', 'status' => 'pending']);

    Livewire::actingAs($admin)
        ->test('pages::admin.companies.index')
        ->assertSee('جهة النفط')
        ->assertSee('جهة الغاز')
        ->call('setTab', 'pending')
        ->assertSee('جهة الغاز')
        ->assertDontSee('جهة النفط');
});

test('the create dialog validates the name and persists with the approved default', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.companies.index')
        ->call('createCompany')
        ->assertHasErrors(['createName' => 'required']);

    Livewire::actingAs($admin)
        ->test('pages::admin.companies.index')
        ->set('createName', 'جهة جديدة')
        ->set('createType', 'government')
        ->set('createWebsite', 'https://example.com')
        ->call('createCompany')
        ->assertHasNoErrors()
        ->assertDispatched('close-dialog', id: 'create-company')
        ->assertDispatched('toast');

    $company = Company::query()->where('name', 'جهة جديدة')->first();

    expect($company)->not->toBeNull()
        ->and($company->status)->toBe('approved')
        ->and($company->type->value)->toBe('government')
        ->and($company->website)->toBe('https://example.com');
});

test('the edit dialog updates name, type, and website', function () {
    $admin = User::factory()->admin()->create();
    $company = companiesPageCompany(['name' => 'اسم قديم', 'type' => 'private']);

    Livewire::actingAs($admin)
        ->test('pages::admin.companies.show', ['company' => $company])
        ->set('editName', 'اسم جديد')
        ->set('editType', 'nonprofit')
        ->set('editWebsite', 'https://new.example.com')
        ->call('updateCompany')
        ->assertHasNoErrors()
        ->assertDispatched('close-dialog', id: 'edit-company')
        ->assertDispatched('toast');

    $company->refresh();

    expect($company->name)->toBe('اسم جديد')
        ->and($company->type->value)->toBe('nonprofit')
        ->and($company->website)->toBe('https://new.example.com');
});

test('deleting a company removes its ratings and redirects to the index', function () {
    $admin = User::factory()->admin()->create();
    $company = companiesPageCompany(['name' => 'جهة للحذف']);
    $rating = companiesPageRating($company);

    Livewire::actingAs($admin)
        ->test('pages::admin.companies.show', ['company' => $company])
        ->call('deleteCompany')
        ->assertRedirect(route('admin.companies.index'));

    expect(Company::query()->find($company->id))->toBeNull()
        ->and(Rating::query()->find($rating->id))->toBeNull();
});

test('the show page renders a pending company and lists its ratings', function () {
    $admin = User::factory()->admin()->create();
    $company = companiesPageCompany(['name' => 'جهة قيد المراجعة', 'status' => 'pending']);
    companiesPageRating($company, ['role_title' => 'مطور واجهات أمامية']);

    $this->actingAs($admin)
        ->get(route('admin.companies.show', $company))
        ->assertOk();

    Livewire::actingAs($admin)
        ->test('pages::admin.companies.show', ['company' => $company])
        ->assertSee('جهة قيد المراجعة')
        ->assertSee('مطور واجهات أمامية');
});

test('a company can be approved from the index', function () {
    $admin = User::factory()->admin()->create();
    $company = companiesPageCompany(['name' => 'جهة معلقة', 'status' => 'pending']);

    Livewire::actingAs($admin)
        ->test('pages::admin.companies.index')
        ->call('moderate', 'company', $company->id, 'approved')
        ->assertDispatched('toast');

    expect($company->fresh()->status)->toBe('approved');
});

test('rejecting a company that has ratings still works through moderate', function () {
    $admin = User::factory()->admin()->create();
    $company = companiesPageCompany(['name' => 'جهة بتقييمات', 'status' => 'pending']);
    companiesPageRating($company);

    Livewire::actingAs($admin)
        ->test('pages::admin.companies.index')
        ->call('moderate', 'company', $company->id, 'rejected')
        ->assertDispatched('toast');

    expect($company->fresh()->status)->toBe('rejected');
});

test('sorting by average rating keeps unrated companies last and ignores unknown fields', function () {
    $admin = User::factory()->admin()->create();
    $rated = companiesPageCompany(['name' => 'جهة مقيمة']);
    companiesPageCompany(['name' => 'جهة بلا تقييم']);
    companiesPageRating($rated, ['status' => 'approved']);

    $page = Livewire::actingAs($admin)->test('pages::admin.companies.index');

    $page->call('sortBy', 'approved_ratings_avg')
        ->assertSet('sortField', 'approved_ratings_avg')
        ->assertSet('sortDirection', 'desc');
    expect(mb_strpos($page->html(), 'جهة مقيمة'))->toBeLessThan(mb_strpos($page->html(), 'جهة بلا تقييم'));

    // Ascending too: null averages stay last, they never float to the top.
    $page->call('sortBy', 'approved_ratings_avg')
        ->assertSet('sortDirection', 'asc');
    expect(mb_strpos($page->html(), 'جهة مقيمة'))->toBeLessThan(mb_strpos($page->html(), 'جهة بلا تقييم'));

    $page->call('sortBy', 'name; drop table companies')
        ->assertSet('sortField', 'approved_ratings_avg');
});

test('a pending duplicate of an approved company is flagged on the index and show pages', function () {
    $admin = User::factory()->admin()->create();
    companiesPageCompany(['name' => 'شركة أرامكو السعودية', 'status' => 'approved']);
    $duplicate = companiesPageCompany(['name' => 'ارامكو', 'status' => 'pending']);

    Livewire::actingAs($admin)
        ->test('pages::admin.companies.index')
        ->assertSee('مكررة؟');

    Livewire::actingAs($admin)
        ->test('pages::admin.companies.show', ['company' => $duplicate])
        ->assertSee('نسخة مكررة')
        ->assertSee('شركة أرامكو السعودية');
});
