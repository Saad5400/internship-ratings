<?php

use App\Models\Company;
use App\Models\Rating;
use Database\Seeders\RealDataSeeder;

beforeEach(function () {
    $this->seed(RealDataSeeder::class);
});

test('ratings are stamped with dates extracted from the source CSVs', function () {
    // Every imported rating should have a non-default timestamp. The earliest
    // CSV row in the data (File #2) is from 2019-09-25.
    expect(Rating::whereBetween('created_at', ['2019-01-01', '2027-01-01'])->count())
        ->toBe(Rating::count());

    // Specific anchor rows — the first File #2 row (kacst review about
    // caramellaapp) was submitted 25/09/2019 11:04:09.
    $firstFile2Row = Rating::where('review_text', 'like', '%caramellaapp%')->firstOrFail();
    expect($firstFile2Row->created_at->format('Y-m-d H:i:s'))
        ->toBe('2019-09-25 11:04:09');

    // First File #7 (Summer 1443) row is from 2022-08-27 17:16:53.
    $firstFile7Row = Rating::where('review_text', 'like', '%روبوت مسارات%')->firstOrFail();
    expect($firstFile7Row->created_at->format('Y-m-d H:i:s'))
        ->toBe('2022-08-27 17:16:53');

    // First File #1 (2025 cohort) row: جمعية عون التقنيه — 16/06/2025 16:11:07.
    $firstFile1Row = Rating::where('review_text', 'like', '%برمجة لغة flutter%')->firstOrFail();
    expect($firstFile1Row->created_at->format('Y-m-d H:i:s'))
        ->toBe('2025-06-16 16:11:07');

    // updated_at should match created_at for imported rows.
    Rating::query()->each(function (Rating $rating) {
        expect($rating->updated_at->format('Y-m-d H:i:s'))
            ->toBe($rating->created_at->format('Y-m-d H:i:s'));
    });
});

test('companies inherit the earliest rating timestamp, or fall back to 2024', function () {
    // KACST has ratings from 2019 — its created_at should match the earliest.
    $kacst = Company::where('name', 'like', 'مدينة الملك عبدالعزيز للعلوم والتقنية%')->firstOrFail();
    $earliestKacst = $kacst->ratings()->min('created_at');
    expect($kacst->created_at->format('Y-m-d H:i:s'))->toBe($earliestKacst);

    // A company that appears only in the COOP directory (File #5) has no
    // ratings, so it keeps the fallback 2024 date.
    $coopOnly = Company::doesntHave('ratings')->first();
    expect($coopOnly)->not->toBeNull();
    expect($coopOnly->created_at->format('Y-m-d'))->toBe('2024-01-01');
});
