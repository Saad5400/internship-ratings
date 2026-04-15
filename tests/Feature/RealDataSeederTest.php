<?php

use Database\Seeders\RealDataSeeder;
use App\Models\Company;
use App\Models\Rating;

test('real data seeder imports and merges csv data into approved companies and ratings', function () {
    $this->seed(RealDataSeeder::class);

    $companiesCount = Company::query()->count();

    expect($companiesCount)->toBeGreaterThan(120)
        ->and(Rating::query()->count())->toBeGreaterThan(250)
        ->and(Company::query()->where('status', 'approved')->count())->toBe($companiesCount);

    expect(Company::query()->whereNotNull('website')->exists())->toBeTrue()
        ->and(Company::query()->whereNull('website')->exists())->toBeTrue()
        ->and(Rating::query()->whereNotNull('contact_method')->exists())->toBeTrue()
        ->and(Rating::query()->whereNotNull('application_method')->exists())->toBeTrue();

    $sampleRating = Rating::query()->first();

    expect($sampleRating)->not->toBeNull()
        ->and($sampleRating->overall_rating)->toBeGreaterThanOrEqual(1.0)
        ->and($sampleRating->overall_rating)->toBeLessThanOrEqual(5.0);
});

test('real data seeder is idempotent and does not create duplicates on rerun', function () {
    $this->seed(RealDataSeeder::class);

    $initialCompanyCount = Company::query()->count();
    $initialRatingCount = Rating::query()->count();

    $this->seed(RealDataSeeder::class);

    expect(Company::query()->count())->toBe($initialCompanyCount)
        ->and(Rating::query()->count())->toBe($initialRatingCount);
});
