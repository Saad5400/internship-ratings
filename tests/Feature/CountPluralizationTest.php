<?php

use App\Models\Company;
use App\Models\Rating;

/**
 * Regression coverage for lang/ar/counts.php wired through
 * x-public.count-noun and trans_choice(): counts next to a noun must use
 * correct Arabic grammar rather than a bare numeral + hardcoded ternary.
 */

/**
 * @param  array<string, mixed>  $attributes
 */
function pluralizationRating(Company $company, array $attributes = []): Rating
{
    return Rating::create(array_merge([
        'company_id' => $company->id,
        'role_title' => 'مطور',
        'city' => 'الرياض',
        'duration_months' => 3,
        'modality' => 'onsite',
        'stipend_sar' => 3000,
        'recommendation' => 'yes',
        'rating_mentorship' => 4,
        'rating_learning' => 4,
        'rating_real_work' => 4,
        'rating_team_environment' => 4,
        'rating_organization' => 4,
    ], $attributes));
}

test('a company with exactly two approved ratings renders the dual form on its show page', function () {
    $company = Company::create(['name' => 'شركة بتقييمين', 'status' => 'approved']);
    pluralizationRating($company);
    pluralizationRating($company);

    $response = $this->get(route('companies.show', $company));

    $response->assertOk();
    $response->assertSee('تقييمان');
    $response->assertDontSee('2 تقييم');
});

test('a company with exactly two approved ratings renders the dual form on its card', function () {
    $company = Company::create(['name' => 'شركة بتقييمين', 'status' => 'approved']);
    pluralizationRating($company);
    pluralizationRating($company);

    $response = $this->get(route('companies.index'));

    $response->assertOk();
    $response->assertSee('تقييمان');
    $response->assertDontSee('2 تقييم');
});

test('a rating with a 12 month duration renders the correct Arabic plural form', function () {
    $company = Company::create(['name' => 'شركة تدريب طويلة', 'status' => 'approved']);
    pluralizationRating($company, ['duration_months' => 12]);

    $response = $this->get(route('companies.show', $company));

    $response->assertOk();
    $response->assertSee('12 شهرًا');
});
