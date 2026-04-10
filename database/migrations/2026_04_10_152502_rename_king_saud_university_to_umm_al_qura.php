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
 * Both company rows are renamed and their name_normalized index is updated.
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
        'جامعة الملك سعود للعلوم الصحية' => 'جامعة أم القرى',
        'جامعة الملك سعود-عمادة التعاملات الإلكترونية والاتصالات' => 'جامعة أم القرى-عمادة التعاملات الإلكترونية والاتصالات',
    ];

    public function up(): void
    {
        foreach (self::RENAMES as $oldName => $newName) {
            DB::table('companies')
                ->where('name', $oldName)
                ->update([
                    'name' => $newName,
                    'name_normalized' => Arabic::normalize($newName),
                ]);
        }
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
