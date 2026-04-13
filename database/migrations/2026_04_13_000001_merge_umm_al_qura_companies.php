<?php

use App\Support\Arabic;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Merges the two Umm Al-Qura University company rows that were produced by
 * the original rename migration (2026_04_10_152502_rename_king_saud…) into a
 * single canonical row named "جامعة أم القرى العمادة التقنية".
 *
 * Background
 * ----------
 * The previous migration corrected two mis-attributed "King Saud" rows:
 *   A  "جامعة أم القرى"                                          (2023 survey — IT rotation)
 *   B  "جامعة أم القرى-عمادة التعاملات الإلكترونية والاتصالات"  (2019 survey — network engineer)
 *
 * Both belong to the same institution; the canonical display name agreed on
 * is "جامعة أم القرى العمادة التقنية".
 *
 * Strategy
 * --------
 *  1. If BOTH old intermediary names still exist → rename B to the canonical,
 *     reassign A's ratings to B, delete A.
 *  2. If only one intermediary still exists → rename it in-place.
 *  3. If neither exists (fresh DB seeded after the seeder fix) → no-op.
 *
 * Idempotent: safe to re-run; if the canonical row already exists the merge
 * logic falls through gracefully via the same strategy as the rename migration.
 *
 *     php artisan migrate
 */
return new class extends Migration
{
    private const CANONICAL = 'جامعة أم القرى العمادة التقنية';

    /** Names that should be replaced / merged into CANONICAL. */
    private const OLD_NAMES = [
        'جامعة أم القرى-عمادة التعاملات الإلكترونية والاتصالات', // B — deanship (2019)
        'جامعة أم القرى',                                          // A — IT rotation (2023)
    ];

    public function up(): void
    {
        DB::transaction(function () {
            $canonical = Arabic::normalize(self::CANONICAL);

            foreach (self::OLD_NAMES as $oldName) {
                $old = DB::table('companies')->where('name', $oldName)->first();
                if ($old === null) {
                    continue; // Already gone — nothing to do.
                }

                $existing = DB::table('companies')
                    ->where('name_normalized', $canonical)
                    ->first();

                if ($existing === null) {
                    // Safe to rename in place.
                    DB::table('companies')
                        ->where('id', $old->id)
                        ->update([
                            'name'            => self::CANONICAL,
                            'name_normalized' => $canonical,
                        ]);
                } else {
                    // Canonical row already exists; reassign ratings then delete stale row.
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
        // The reverse is intentionally left as a no-op: reconstructing two
        // separate rows from a merged one is not safely reversible.
    }
};
