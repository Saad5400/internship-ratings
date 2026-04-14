<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Orchestrates every real-data seeder in one go.
 *
 *     php artisan db:seed --class=RealDataSeeder
 *
 * Each child seeder is also independently runnable. They are all idempotent
 * (companies via firstOrCreate on normalized name, ratings via a per-row
 * fingerprint), so re-running this orchestrator is safe in prod.
 *
 * Order matters only for efficiency: the COOP directory runs first so the
 * review seeders can reuse those company rows instead of creating duplicates.
 */
class RealDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([

        ]);
    }
}
