<?php

use App\Support\Arabic;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrects company names that were incorrectly attributed to King Saud
 * University (جامعة الملك سعود) instead of Umm Al Qura University
 * (جامعة أم القرى) in the original seed data.
 *
 * Two entries were affected:
 *  1. "جامعة الملك سعود للعلوم الصحية" — Row 26 of the 2023 survey
 *  2. "جامعة الملك سعود-عمادة التعاملات الإلكترونية والاتصالات" — idx=80 of the 2019 survey
 *
 * Both correct names now resolve to the single canonical form
 * "جامعة أم القرى العمادة التقنية" (see the merge migration that follows).
 *
 * Strategy per old row:
 *  - If the correct name does NOT yet exist → rename the row in place.
 *  - If the correct name ALREADY exists as a separate row → reassign all
 *    ratings from the old (wrong) row to the existing correct row, then
 *    delete the old row. This prevents producing duplicates in a live DB
 *    where the correct name was already seeded independently.
 *
 * The migration is a no-op when the old names do not exist (i.e. the DB was
 * seeded after the seeder fix was applied).
 */
return new class extends Migration
{
    /**
     * Old name => correct name pairs.
     *
     * @var array<string, string>
     */
    private const RENAMES = [
        'جامعة الملك سعود للعلوم الصحية' => 'جامعة أم القرى العمادة التقنية',
        'جامعة الملك سعود-عمادة التعاملات الإلكترونية والاتصالات' => 'جامعة أم القرى العمادة التقنية',
    ];

    public function up(): void
    {
        DB::transaction(function () {
            foreach (self::RENAMES as $oldName => $newName) {
                $old = DB::table('companies')->where('name', $oldName)->first();
                if ($old === null) {
                    // Already fixed or never seeded — nothing to do.
                    continue;
                }

                $existing = DB::table('companies')->where('name', $newName)->first();

                if ($existing === null) {
                    // Safe to rename in place; no duplicate will be created.
                    DB::table('companies')
                        ->where('id', $old->id)
                        ->update([
                            'name' => $newName,
                            'name_normalized' => Arabic::normalize($newName),
                        ]);
                } else {
                    // The correct company row already exists. Reassign all
                    // ratings from the stale row to the correct one, then
                    // delete the stale row to avoid duplicates.
                    DB::table('ratings')
                        ->where('company_id', $old->id)
                        ->update(['company_id' => $existing->id]);

                    DB::table('companies')->where('id', $old->id)->delete();
                }
            }
        });
    }

    public function down(): void
    {
        foreach (self::RENAMES as $oldName => $newName) {
            DB::table('companies')
                ->where('name', $newName)
                ->update([
                    'name' => $oldName,
                    'name_normalized' => Arabic::normalize($oldName),
                ]);
        }
    }
};
