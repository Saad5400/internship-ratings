<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Self-healing search index.
 *
 * A fresh release starts with an empty index and search falls back to literal
 * name matching until something builds it — this is that something, so no
 * deploy depends on a human remembering. It is also the repair path for rows a
 * failed queue job left without a vector.
 *
 * Cheap to repeat: unchanged field text is skipped by content hash, so a run
 * with nothing to do makes no embedding calls at all.
 */
Schedule::command('search:index')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

/*
 * Moderation digest email. Runs daily, but the every-two-days cadence lives
 * in the command's cache guard — the daily slot just gives it a chance to
 * fire, so a missed day self-heals the next morning instead of waiting for
 * the next matching cron day. 06:00 UTC is 09:00 in Riyadh. onOneServer()
 * (shared database cache lock) keeps a scaled-out deployment from sending
 * the digest once per container.
 */
Schedule::command('moderation:digest')
    ->dailyAt('06:00')
    ->onOneServer();
