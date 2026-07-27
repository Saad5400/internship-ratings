<?php

/*
 * Pins lang/ar/counts.php — the single source of truth for Arabic CLDR
 * plural forms (zero|one|two|few|many|other) used whenever a count is
 * rendered next to a noun. App locale is 'ar', so trans_choice() resolves
 * through Illuminate\Translation\MessageSelector's Arabic plural rule.
 */

test('ratings count uses the correct Arabic CLDR plural form', function (int $count, string $expected) {
    expect(trans_choice('counts.ratings', $count))->toBe($expected);
})->with([
    [0, 'لا توجد تقييمات'],
    [1, 'تقييم واحد'],
    [2, 'تقييمان'],
    [3, '3 تقييمات'],
    [10, '10 تقييمات'],
    [11, '11 تقييمًا'],
    [99, '99 تقييمًا'],
    [100, '100 تقييم'],
    [103, '103 تقييمات'],
    [111, '111 تقييمًا'],
]);

test('companies count uses the correct Arabic CLDR plural form', function (int $count, string $expected) {
    expect(trans_choice('counts.companies', $count))->toBe($expected);
})->with([
    [0, 'لا توجد جهات'],
    [1, 'جهة واحدة'],
    [2, 'جهتان'],
    [3, '3 جهات'],
    [10, '10 جهات'],
    [11, '11 جهةً'],
    [99, '99 جهةً'],
    [100, '100 جهة'],
    [103, '103 جهات'],
    [111, '111 جهةً'],
]);

test('months count uses the correct Arabic CLDR plural form', function (int $count, string $expected) {
    expect(trans_choice('counts.months', $count))->toBe($expected);
})->with([
    [0, 'أقل من شهر'],
    [1, 'شهر واحد'],
    [2, 'شهران'],
    [3, '3 أشهر'],
    [10, '10 أشهر'],
    [11, '11 شهرًا'],
    [12, '12 شهرًا'],
]);
