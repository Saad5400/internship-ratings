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
 * The queue, drained once a minute instead of by a resident worker.
 *
 * The only queued job is IndexSearchDocuments (a company or rating changed),
 * which is rare, so a `queue:work` sleeping in a loop all day held ~65MB for
 * nothing. Search freshness tolerates the up-to-a-minute delay.
 *
 * withoutOverlapping(10): the lock lives in the cache store, so it must
 * expire on its own if a container dies mid-run; 10 minutes comfortably
 * exceeds --max-time plus one job's timeout. runInBackground keeps a slow
 * drain from delaying the rest of this minute's schedule.
 */
Schedule::command('queue:work', ['--stop-when-empty', '--tries=3', '--max-time=240'])
    ->everyMinute()
    ->withoutOverlapping(10)
    ->runInBackground();
