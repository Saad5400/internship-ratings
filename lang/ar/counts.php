<?php

/**
 * Single source of truth for Arabic CLDR plural forms used when rendering a
 * count next to a noun (ratings, companies, months). Six pipe-separated
 * forms in order: zero|one|two|few|many|other — see
 * \Illuminate\Translation\MessageSelector::getPluralIndex() for the 'ar'
 * locale rule that picks among them.
 *
 * The `*_noun` variants omit `:count` and are used beside an already
 * animated number (see resources/views/components/public/count-noun.blade.php).
 */
return [
    'ratings' => 'لا توجد تقييمات|تقييم واحد|تقييمان|:count تقييمات|:count تقييمًا|:count تقييم',
    'ratings_noun' => 'تقييمات|تقييم|تقييمان|تقييمات|تقييمًا|تقييم',

    'companies' => 'لا توجد جهات|جهة واحدة|جهتان|:count جهات|:count جهةً|:count جهة',
    'companies_noun' => 'جهات|جهة|جهتان|جهات|جهةً|جهة',

    'months' => 'أقل من شهر|شهر واحد|شهران|:count أشهر|:count شهرًا|:count شهر',
    'months_noun' => 'أشهر|شهر|شهران|أشهر|شهرًا|شهر',
];
