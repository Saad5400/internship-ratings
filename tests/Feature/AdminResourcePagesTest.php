<?php

use App\Enums\SaudiCity;
use App\Models\Company;
use App\Models\Rating;
use App\Models\User;

/**
 * @return array<string, mixed>
 */
function adminRatingPayload(Company $company, array $overrides = []): array
{
    return array_merge([
        'company_id' => $company->id,
        'role_title' => 'مهندس برمجيات',
        'city' => SaudiCity::Riyadh->value,
        'modality' => 'onsite',
        'rating_learning' => 5,
        'rating_mentorship' => 4,
        'rating_real_work' => 4,
        'rating_team_environment' => 3,
        'rating_organization' => 5,
        'status' => 'approved',
    ], $overrides);
}

/**
 * These smoke tests render each Filament admin page end-to-end so that a
 * broken import (such as a layout component pulled from the wrong namespace)
 * fails the suite instead of only surfacing at runtime.
 */
test('the ratings view page renders for an admin', function () {
    $admin = User::factory()->admin()->create();
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);
    $rating = Rating::create(adminRatingPayload($company));

    $this->actingAs($admin)
        ->get("/filament/ratings/{$rating->id}")
        ->assertOk()
        ->assertSee('التقييمات', false);
});

test('the ratings edit page renders for an admin', function () {
    $admin = User::factory()->admin()->create();
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);
    $rating = Rating::create(adminRatingPayload($company));

    $this->actingAs($admin)
        ->get("/filament/ratings/{$rating->id}/edit")
        ->assertOk();
});

test('the ratings list and create pages render for an admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/filament/ratings')->assertOk();
    $this->actingAs($admin)->get('/filament/ratings/create')->assertOk();
});

test('the companies resource pages render for an admin', function () {
    $admin = User::factory()->admin()->create();
    $company = Company::create(['name' => 'شركة تجريبية', 'type' => 'private', 'status' => 'approved']);

    $this->actingAs($admin)->get('/filament/companies')->assertOk();
    $this->actingAs($admin)->get('/filament/companies/create')->assertOk();
    $this->actingAs($admin)->get("/filament/companies/{$company->id}")->assertOk();
    $this->actingAs($admin)->get("/filament/companies/{$company->id}/edit")->assertOk();
});

test('the users resource pages render for an admin', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)->get('/filament/users')->assertOk();
    $this->actingAs($admin)->get('/filament/users/create')->assertOk();
    $this->actingAs($admin)->get("/filament/users/{$user->id}/edit")->assertOk();
});
