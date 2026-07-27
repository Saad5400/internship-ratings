<?php

use App\Models\Company;
use App\Models\Rating;
use App\Models\User;
use Livewire\Livewire;

/**
 * @param  array<string, mixed>  $overrides
 */
function dashboardPendingRating(Company $company, array $overrides = []): Rating
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

test('the dashboard lists pending ratings and companies', function () {
    $admin = User::factory()->admin()->create();
    $company = Company::create(['name' => 'جهة معتمدة', 'type' => 'private', 'status' => 'approved']);
    $pendingCompany = Company::create(['name' => 'جهة قيد المراجعة', 'type' => 'private', 'status' => 'pending']);
    dashboardPendingRating($company, ['role_title' => 'محلل بيانات']);

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->assertSee('جهة قيد المراجعة')
        ->assertSee('محلل بيانات')
        ->assertSee('الإحصائيات');
});

test('approving a rating from the queue publishes it', function () {
    $admin = User::factory()->admin()->create();
    $company = Company::create(['name' => 'جهة', 'type' => 'private', 'status' => 'approved']);
    $rating = dashboardPendingRating($company);

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->call('moderate', 'rating', $rating->id, 'approved')
        ->assertDispatched('toast');

    expect($rating->fresh()->status)->toBe('approved');
});

test('rejecting a company from the queue updates its status', function () {
    $admin = User::factory()->admin()->create();
    $company = Company::create(['name' => 'جهة مرفوضة', 'type' => 'private', 'status' => 'pending']);

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->call('moderate', 'company', $company->id, 'rejected');

    expect($company->fresh()->status)->toBe('rejected');
});

test('undo restores the previous moderation status', function () {
    $admin = User::factory()->admin()->create();
    $company = Company::create(['name' => 'جهة', 'type' => 'private', 'status' => 'approved']);
    $rating = dashboardPendingRating($company);

    $page = Livewire::actingAs($admin)->test('pages::admin.dashboard');

    $page->call('moderate', 'rating', $rating->id, 'approved');
    expect($rating->fresh()->status)->toBe('approved');

    $page->call('undoModeration', 'rating', $rating->id, 'pending');
    expect($rating->fresh()->status)->toBe('pending');
});

test('moderation rejects unknown statuses and types', function () {
    $admin = User::factory()->admin()->create();
    $company = Company::create(['name' => 'جهة', 'type' => 'private', 'status' => 'approved']);
    $rating = dashboardPendingRating($company);

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->call('moderate', 'rating', $rating->id, 'deleted')
        ->assertStatus(400);

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->call('moderate', 'user', $rating->id, 'approved')
        ->assertStatus(400);

    expect($rating->fresh()->status)->toBe('pending');
});

test('the ratings queue paginates with load more', function () {
    $admin = User::factory()->admin()->create();
    $company = Company::create(['name' => 'جهة', 'type' => 'private', 'status' => 'approved']);

    foreach (range(1, 7) as $i) {
        dashboardPendingRating($company, ['role_title' => "وظيفة رقم {$i}"]);
    }

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->assertSee('وظيفة رقم 5')
        ->assertDontSee('وظيفة رقم 6')
        ->call('loadMoreRatings')
        ->assertSee('وظيفة رقم 6')
        ->assertSee('وظيفة رقم 7');
});

test('the dashboard celebrates an empty queue', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->assertSee('كل شيء تمت مراجعته');
});

test('a rating can be reassigned to another company and undone', function () {
    $admin = User::factory()->admin()->create();
    $duplicate = Company::create(['name' => 'ارامكو', 'type' => 'private', 'status' => 'pending']);
    $original = Company::create(['name' => 'شركة أرامكو السعودية', 'type' => 'private', 'status' => 'approved']);
    $rating = dashboardPendingRating($duplicate);

    $page = Livewire::actingAs($admin)->test('pages::admin.dashboard');

    $page->call('reassignRating', $rating->id, $original->id)
        ->assertDispatched('toast');
    expect($rating->fresh()->company_id)->toBe($original->id);

    $page->call('undoReassignRating', $rating->id, $duplicate->id);
    expect($rating->fresh()->company_id)->toBe($duplicate->id);
});

test('the manual reassign picker moves the rating', function () {
    $admin = User::factory()->admin()->create();
    $from = Company::create(['name' => 'جهة أولى', 'type' => 'private', 'status' => 'approved']);
    $to = Company::create(['name' => 'جهة ثانية', 'type' => 'private', 'status' => 'approved']);
    $rating = dashboardPendingRating($from);

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->call('startReassign', $rating->id)
        ->set('reassignCompanyId', $to->id)
        ->call('confirmReassign')
        ->assertSet('reassignRatingId', null);

    expect($rating->fresh()->company_id)->toBe($to->id);
});

test('a pending company similar to an approved one is flagged as a duplicate', function () {
    $admin = User::factory()->admin()->create();
    Company::create(['name' => 'شركة أرامكو السعودية', 'type' => 'private', 'status' => 'approved']);
    $duplicate = Company::create(['name' => 'ارامكو', 'type' => 'private', 'status' => 'pending']);
    dashboardPendingRating($duplicate);

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->assertSee('نسخة مكررة')
        ->assertSee('نقل التقييم إليها');
});

test('rejecting a company that has ratings requires confirmation with counts', function () {
    $admin = User::factory()->admin()->create();
    $company = Company::create(['name' => 'جهة بتقييمات', 'type' => 'private', 'status' => 'pending']);
    dashboardPendingRating($company);

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->assertSee('لدى هذه الجهة تقييم واحد');
});
