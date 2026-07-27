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
