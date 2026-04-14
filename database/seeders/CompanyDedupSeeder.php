<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Merges duplicate company rows that survived Arabic::normalize()-based
 * dedup in RealDataSeeder. These are companies whose normalized names are
 * genuinely different strings (prefix differences like "شركة", word-order
 * swaps, district suffixes, bilingual variants, etc.) but which refer to
 * the same real-world entity.
 *
 * Idempotent: if the alias ids no longer exist (because this seeder has
 * already run), the cluster is silently skipped. A re-run on an already
 * deduped DB produces zero changes.
 *
 *     php artisan db:seed --class=CompanyDedupSeeder
 */
class CompanyDedupSeeder extends Seeder
{
    /**
     * Each cluster:
     *   - canonical: id of the row to KEEP
     *   - aliases:   ids to MERGE INTO canonical (ratings reassigned, rows deleted)
     *   - reason:    short note on why we believe they're the same entity
     *
     * Canonical selection rule: company with the most ratings wins, ties
     * broken by shortest / cleanest display name. Canonical's name is left
     * untouched; website/description are backfilled from aliases (first
     * non-null wins) only if the canonical field is currently null.
     */
    private const MERGE_PLAN = [
        [
            'canonical' => 128, // "الاساليب الذكية" (8 ratings)
            'aliases' => [166], // "شركة الأساليب الذكية" (1 rating)
            'reason' => 'Exact match after stripping "شركة" prefix.',
        ],
        [
            'canonical' => 158, // "أمانة العاصمة المقدسة" (7 ratings)
            'aliases' => [172, 180, 170],
            // 172 = "امانة العاصمة" (missing "المقدسة"),
            // 180 = "أمانة العاصمة المقدسة - وكالة الحلول الرقمية" (district/agency suffix)
            // 170 = "أمانة مكة" (shortened colloquial form — same body)
            'reason' => 'Makkah Municipality — base name vs truncated vs sub-agency suffix vs colloquial short form.',
        ],
        [
            'canonical' => 156, // "مستشفى الملك عبدالعزيز" (5 ratings)
            'aliases' => [173], // "مستشفى الملك عبد العزيز" (extra space only)
            'reason' => 'Same hospital; only difference is a space inside "عبدالعزيز".',
        ],
        [
            'canonical' => 177, // "جمعية عون التقنية" (3 ratings)
            'aliases' => [179], // "عون التقنية" (1 rating)
            'reason' => 'Exact match after stripping "جمعية" prefix.',
        ],
        [
            'canonical' => 133, // "ارامكو السعودية" (1 rating)
            'aliases' => [121, 83],
            // 121 = "شركة ارامكو السعودية" (1 rating) — exact match after stripping "شركة"
            // 83  = "Aramco" (0 ratings) — English label for the same entity
            'reason' => 'Saudi Aramco — Arabic with/without "شركة" + bare English label.',
        ],
        [
            'canonical' => 115, // "أمانة منطقة الرياض" (1 rating)
            'aliases' => [117], // "امانة الرياض" (1 rating)
            'reason' => 'Riyadh Municipality — full vs short form of the same body.',
        ],
        [
            'canonical' => 136, // "تحكم" (1 rating)
            'aliases' => [139, 144],
            // 139 = "تحكم (المسؤولة عن نظام ساهر)" — same company with a parenthetical
            // 144 = "تحكم للتحكم التقني والأمني الشامل TAHAKOM" — full registered name
            'reason' => 'Tahakom — short name vs parenthetical note vs full legal name.',
        ],
        [
            'canonical' => 157, // "إيسار" (1 rating)
            'aliases' => [176], // "شركة إيسار" (1 rating)
            'reason' => 'Exact match after stripping "شركة" prefix. NOTE: we do NOT'
                .' merge id 167 ("شركة إيسار لتقنية المعلومات") since the IT division'
                .' qualifier makes it plausibly a separate entity.',
        ],
        [
            'canonical' => 126, // "T2" (2 ratings)
            'aliases' => [129], // "T2 Business Research and Development" (1 rating)
            'reason' => 'Same company — 129 is the spelled-out form of the T2 acronym.',
        ],
        [
            'canonical' => 141, // "بنك الانماء" (1 rating)
            'aliases' => [71], // "مصرف الانماء" (0 ratings)
            'reason' => 'Alinma Bank — "بنك" and "مصرف" are interchangeable for this bank.',
        ],
        [
            'canonical' => 145, // "شركة علم" (1 rating)
            'aliases' => [112], // "شركة علم لأمن المعلومات" (1 rating)
            'reason' => 'Elm Company — the suffix "لأمن المعلومات" is a descriptor of'
                .' the same Saudi IT company.',
        ],
        [
            'canonical' => 135, // "مدينة الملك عبدالعزيز للعلوم والتقنية - كاكست" (1 rating)
            'aliases' => [150], // "King Abdulaziz city for science and technology" (1 rating)
            'reason' => 'KACST — Arabic and English labels for the same organization.',
        ],
        [
            'canonical' => 127, // "مدينة الملك فهد الطبية" (1 rating)
            'aliases' => [134], // "King Fahad Medical City" (1 rating)
            'reason' => 'King Fahad Medical City — Arabic and English labels.',
        ],
        [
            'canonical' => 132, // "سدايا" (4 ratings) — informal abbreviation
            'aliases' => [1],
            // 1 = "الهيئة السعودية للبيانات والذكاء الاصطناعي" (1 rating, COOP entry with sdaia.gov.sa URL)
            'reason' => 'SDAIA — abbreviated brand name "سدايا" vs full official Arabic name. '
                .'Backfill copies sdaia.gov.sa / description from COOP entry to the abbreviation row.',
        ],
        [
            'canonical' => 50, // "وزارة الاتصالات وتقنية المعلومات" (0 ratings, COOP, website=mcit.gov.sa)
            'aliases' => [124],
            // 124 = "وزارة الاتصالات وتقنية المعلومات MCIT" (1 rating) — same ministry, English acronym appended
            'reason' => 'MCIT — same ministry; "MCIT" suffix is just the English acronym. '
                .'COOP entry kept as canonical for the cleaner Arabic display name.',
        ],
        [
            'canonical' => 51, // "وزارة البيئة والمياه والزراعة" (0 ratings, COOP, website=mewa.gov.sa)
            'aliases' => [137],
            // 137 = "MEWA | وزارة البيئة والمياه والزراعة" (1 rating) — same ministry, English acronym prepended
            'reason' => 'MEWA — same ministry; "MEWA |" prefix is just the English acronym. '
                .'COOP entry kept as canonical for the cleaner Arabic display name.',
        ],
    ];

    public function run(): void
    {
        $clustersProcessed = 0;
        $ratingsReassigned = 0;
        $companiesDeleted = 0;

        DB::transaction(function () use (&$clustersProcessed, &$ratingsReassigned, &$companiesDeleted) {
            foreach (self::MERGE_PLAN as $cluster) {
                $canonicalId = $cluster['canonical'];
                $aliasIds = $cluster['aliases'];

                $canonical = Company::find($canonicalId);
                if ($canonical === null) {
                    // Canonical gone — nothing sensible to merge into. Skip.
                    continue;
                }

                $existingAliases = Company::whereIn('id', $aliasIds)->get();
                if ($existingAliases->isEmpty()) {
                    // Already merged on a previous run. Idempotent no-op.
                    continue;
                }

                $existingAliasIds = $existingAliases->pluck('id')->all();

                // Reassign ratings from aliases to canonical.
                $reassigned = DB::table('ratings')
                    ->whereIn('company_id', $existingAliasIds)
                    ->update(['company_id' => $canonicalId]);
                $ratingsReassigned += $reassigned;

                // Backfill website/description from aliases (first non-null wins),
                // only if canonical's value is currently null. Name is never touched.
                $dirty = false;
                if ($canonical->website === null || $canonical->website === '') {
                    foreach ($existingAliases as $alias) {
                        if ($alias->website !== null && $alias->website !== '') {
                            $canonical->website = $alias->website;
                            $dirty = true;
                            break;
                        }
                    }
                }
                if ($canonical->description === null || $canonical->description === '') {
                    foreach ($existingAliases as $alias) {
                        if ($alias->description !== null && $alias->description !== '') {
                            $canonical->description = $alias->description;
                            $dirty = true;
                            break;
                        }
                    }
                }
                if ($dirty) {
                    $canonical->save();
                }

                // Delete alias rows. Ratings have already been reassigned, so
                // cascadeOnDelete has nothing left to remove.
                $deleted = Company::whereIn('id', $existingAliasIds)->delete();
                $companiesDeleted += $deleted;

                $clustersProcessed++;
            }
        });

        $this->command?->info(sprintf(
            'CompanyDedupSeeder: processed %d cluster(s), reassigned %d rating(s), deleted %d compan(y/ies).',
            $clustersProcessed,
            $ratingsReassigned,
            $companiesDeleted
        ));
    }
}
