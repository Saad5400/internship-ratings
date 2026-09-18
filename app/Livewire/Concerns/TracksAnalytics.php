<?php

namespace App\Livewire\Concerns;

use App\Support\Analytics;

/**
 * Counts an in-page action — a search, a filter, a wizard step, a vote — that
 * finishes without a navigation, so there is no next render for
 * {@see Analytics::track()} to ride along on. The event is dispatched to the
 * browser on the same round trip, where resources/js/analytics.js hands it to
 * Umami; it is the same `$this->dispatch()` convention the admin toasts use.
 *
 * Use `Analytics::track()` instead for anything that ends in a redirect: a
 * dispatched event races the navigation that follows it.
 *
 * The privacy and cardinality rules are Analytics' — read them there. They are
 * not weaker on this path just because it is client-side.
 */
trait TracksAnalytics
{
    /**
     * @param  array<string, string|int|bool>  $data  low-cardinality properties only
     */
    protected function trackEvent(string $name, array $data = []): void
    {
        $this->dispatch('analytics', name: $name, data: $data);
    }
}
