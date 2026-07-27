<?php

use App\Models\Company;
use App\Models\Rating;
use App\Models\User;
use Livewire\Livewire;

/**
 * @param  array<string, mixed>  $overrides
 */
function ratingsPageRating(Company $company, array $overrides = []): Rating
{
    return Rating::create(array_merge([
        'company_id' => $company->id,
        'role_title' => 'مهندس برمجيات',
        'modality' => 'onsite',
        'rating_learning' => 4,
        'rating_mentorship' => 4,
        'rating_real_work' => 4,
        'rating_team_environment' => 4,
        'rating_organization' => 4,
        'status' => 'approved',
    ], $overrides));
}

function ratingsPageCompany(string $name = 'جهة تجريبية', array $overrides = []): Company
{
    return Company::create(array_merge([
        'name' => $name,
        'type' => 'private',
        'status' => 'approved',
    ], $overrides));
}

test('the ratings index renders for an admin with ratings visible', function () {
    $admin = User::factory()->admin()->create();
    $company = ratingsPageCompany('شركة الاختبار الكبرى');
    ratingsPageRating($company, ['role_title' => 'محلل بيانات تجريبي']);

    Livewire::actingAs($admin)
        ->test('pages::admin.ratings.index')
        ->assertSee('شركة الاختبار الكبرى')
        ->assertSee('محلل بيانات تجريبي');
});

test('guests and non-admins are blocked from the ratings index', function () {
    $this->get('/admin/ratings')->assertRedirect(route('login'));

    $user = User::factory()->create();
    $this->actingAs($user)->get('/admin/ratings')->assertForbidden();
});

test('the pending tab hides approved ratings', function () {
    $admin = User::factory()->admin()->create();
    $company = ratingsPageCompany();
    ratingsPageRating($company, ['role_title' => 'دور معلق للمراجعة', 'status' => 'pending']);
    ratingsPageRating($company, ['role_title' => 'دور موافق عليه سابقاً', 'status' => 'approved']);

    Livewire::actingAs($admin)
        ->test('pages::admin.ratings.index')
        ->assertSee('دور معلق للمراجعة')
        ->assertSee('دور موافق عليه سابقاً')
        ->call('setTab', 'pending')
        ->assertSet('tab', 'pending')
        ->assertSee('دور معلق للمراجعة')
        ->assertDontSee('دور موافق عليه سابقاً');
});

test('an unknown tab key is ignored', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.ratings.index')
        ->call('setTab', 'bogus')
        ->assertSet('tab', 'all');
});

test('search matches company name and role title', function () {
    $admin = User::factory()->admin()->create();
    $first = ratingsPageCompany('شركة أرامكو السعودية');
    $second = ratingsPageCompany('مصرف الراجحي');
    ratingsPageRating($first, ['role_title' => 'مهندس حقول نفط']);
    ratingsPageRating($second, ['role_title' => 'محلل مخاطر ائتمانية']);

    $page = Livewire::actingAs($admin)->test('pages::admin.ratings.index');

    $page->set('search', 'أرامكو')
        ->assertSee('مهندس حقول نفط')
        ->assertDontSee('محلل مخاطر ائتمانية');

    $page->set('search', 'محلل مخاطر')
        ->assertSee('محلل مخاطر ائتمانية')
        ->assertDontSee('مهندس حقول نفط');
});

test('sortBy rejects fields outside the whitelist', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.ratings.index')
        ->call('sortBy', 'reviewer_name')
        ->assertSet('sortField', 'created_at')
        ->assertSet('sortDirection', 'desc')
        ->call('sortBy', 'overall_rating')
        ->assertSet('sortField', 'overall_rating')
        ->assertSet('sortDirection', 'desc')
        ->call('sortBy', 'overall_rating')
        ->assertSet('sortDirection', 'asc');
});

test('a rating can be approved from the index', function () {
    $admin = User::factory()->admin()->create();
    $company = ratingsPageCompany();
    $rating = ratingsPageRating($company, ['status' => 'pending']);

    Livewire::actingAs($admin)
        ->test('pages::admin.ratings.index')
        ->call('moderate', 'rating', $rating->id, 'approved')
        ->assertDispatched('toast');

    expect($rating->fresh()->status)->toBe('approved');
});

test('the edit page renders the rating workspace', function () {
    $admin = User::factory()->admin()->create();
    $company = ratingsPageCompany('جهة صفحة التعديل');
    $rating = ratingsPageRating($company, ['role_title' => 'مطور تطبيقات جوال']);

    Livewire::actingAs($admin)
        ->test('pages::admin.ratings.edit', ['rating' => $rating])
        ->assertSee('مطور تطبيقات جوال')
        ->assertSee('جهة صفحة التعديل')
        ->assertSee('يُحسب تلقائياً من التقييمات الفرعية');
});

test('saving edits persists fields and the model recomputes the overall rating', function () {
    $admin = User::factory()->admin()->create();
    $company = ratingsPageCompany();
    $rating = ratingsPageRating($company);

    expect($rating->fresh()->overall_rating)->toEqualWithDelta(4.0, 0.001);

    Livewire::actingAs($admin)
        ->test('pages::admin.ratings.edit', ['rating' => $rating])
        ->set('role_title', 'مسمى وظيفي محدث')
        ->set('rating_learning', 2)
        ->set('rating_mentorship', 2)
        ->set('rating_real_work', 2)
        ->set('rating_team_environment', 2)
        ->set('rating_organization', 2)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $fresh = $rating->fresh();

    expect($fresh->role_title)->toBe('مسمى وظيفي محدث')
        ->and($fresh->rating_learning)->toBe(2)
        ->and($fresh->overall_rating)->toEqualWithDelta(2.0, 0.001);
});

test('a missing required metric blocks saving with an arabic message', function () {
    $admin = User::factory()->admin()->create();
    $company = ratingsPageCompany();
    $rating = ratingsPageRating($company);

    Livewire::actingAs($admin)
        ->test('pages::admin.ratings.edit', ['rating' => $rating])
        ->set('rating_learning', null)
        ->call('save')
        ->assertHasErrors(['rating_learning' => 'required'])
        ->assertSee('قيّم هذا الجانب من 1 إلى 5.');

    expect($rating->fresh()->rating_learning)->toBe(4);
});

test('deleting a rating removes it and redirects to the index', function () {
    $admin = User::factory()->admin()->create();
    $company = ratingsPageCompany();
    $rating = ratingsPageRating($company);

    Livewire::actingAs($admin)
        ->test('pages::admin.ratings.edit', ['rating' => $rating])
        ->call('delete')
        ->assertRedirect(route('admin.ratings.index'));

    expect(Rating::query()->find($rating->id))->toBeNull();
});
