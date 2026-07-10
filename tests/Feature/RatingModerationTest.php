<?php

use App\Enums\SaudiCity;
use App\Models\Company;
use App\Models\Rating;
use Livewire\Livewire;

/**
 * @return array<string, mixed>
 */
function moderationRatingPayload(array $overrides = []): array
{
    return array_merge([
        'role_title' => 'مهندس',
        'city' => SaudiCity::Riyadh->value,
        'modality' => 'onsite',
        'rating_learning' => 5,
        'rating_mentorship' => 5,
        'rating_real_work' => 5,
        'rating_team_environment' => 5,
        'rating_organization' => 5,
    ], $overrides);
}

test('pending ratings are hidden and approved ratings are visible on the company page', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    $approved = Rating::create(moderationRatingPayload([
        'company_id' => $company->id,
        'role_title' => 'دور معتمد',
        'status' => 'approved',
    ]));

    $pending = Rating::create(moderationRatingPayload([
        'company_id' => $company->id,
        'role_title' => 'دور معلق',
        'status' => 'pending',
    ]));

    $component = Livewire::test('pages::companies.show', ['company' => $company]);

    $visibleIds = $component->instance()->ratings->pluck('id')->all();

    expect($visibleIds)->toContain($approved->id)
        ->and($visibleIds)->not->toContain($pending->id);
});

test('average_rating and ratings_count only count approved ratings', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    Rating::create(moderationRatingPayload([
        'company_id' => $company->id,
        'status' => 'approved',
        'rating_learning' => 4,
        'rating_mentorship' => 4,
        'rating_real_work' => 4,
        'rating_team_environment' => 4,
        'rating_organization' => 4,
    ])); // overall = 4.0

    Rating::create(moderationRatingPayload([
        'company_id' => $company->id,
        'status' => 'pending',
        'rating_learning' => 1,
        'rating_mentorship' => 1,
        'rating_real_work' => 1,
        'rating_team_environment' => 1,
        'rating_organization' => 1,
    ])); // overall = 1.0, must be ignored

    $company->refresh();

    expect($company->ratings_count)->toBe(1)
        ->and($company->average_rating)->toBe(4.0);
});

test('public submission for existing company persists a pending rating', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    Livewire::test('pages::ratings.create')
        ->set('companyId', (string) $company->id)
        ->set('role_title', 'مهندس برمجيات')
        ->set('city', SaudiCity::Riyadh->value)
        ->set('duration_months', 3)
        ->set('modality', 'onsite')
        ->set('rating_learning', 5)
        ->set('rating_mentorship', 5)
        ->set('rating_real_work', 4)
        ->set('rating_team_environment', 4)
        ->set('rating_organization', 3)
        ->call('save')
        ->assertRedirect(route('companies.show', $company));

    expect(Rating::where('company_id', $company->id)->count())->toBe(1)
        ->and(Rating::first()->status)->toBe('pending');
});

test('approving a pending rating makes it visible on the company page', function () {
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    $rating = Rating::create(moderationRatingPayload([
        'company_id' => $company->id,
        'status' => 'pending',
    ]));

    $before = Livewire::test('pages::companies.show', ['company' => $company])
        ->instance()->ratings->pluck('id')->all();

    expect($before)->not->toContain($rating->id);

    $rating->update(['status' => 'approved']);

    $after = Livewire::test('pages::companies.show', ['company' => $company])
        ->instance()->ratings->pluck('id')->all();

    expect($after)->toContain($rating->id);
});
