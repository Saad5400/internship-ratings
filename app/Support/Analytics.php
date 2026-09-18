<?php

namespace App\Support;

use App\Livewire\Concerns\TracksAnalytics;

/**
 * Product analytics for events the *server* is the only witness to.
 *
 * The interesting half of this app's funnel is invisible to page views. Someone
 * who opens the rating wizard and submits it leaves the same single page view
 * whether the submission worked, was rejected by Turnstile, or was abandoned on
 * step two — all of that happens inside Livewire round trips and a redirect.
 * This flashes such events into the session; `partials/analytics-events` prints
 * the queue on the next full render and `resources/js/analytics.js` hands it to
 * Umami.
 *
 * Use this for anything that precedes a navigation (a submit that redirects).
 * For an action that stays on the page — a filter, a vote, a step change — use
 * {@see TracksAnalytics}, which dispatches the event to
 * the browser immediately instead of waiting for a render that never comes.
 *
 * Three rules, and they are the whole contract:
 *
 * 1. **Never a name, an id, a free-text answer, or anything a person typed.**
 *    This app collects reviews of real employers written by real people, some of
 *    whom leave a name and a way to contact them. None of that may leave the
 *    app. Values are fixed words chosen in the calling code, or bands (see
 *    `band()`). Umami's tables have none of this app's moderation or access
 *    control, so anything sent there is effectively public to anyone who can
 *    read them.
 * 2. **Never the content of a rating.** Not the scores, not the recommendation,
 *    not the stipend. A pending review is not public yet, this site is small
 *    enough that a timestamp plus a score would often single one out, and the
 *    admin dashboard already answers "what are people rating" behind a login.
 *    Analytics answers "is the funnel working", which needs none of it.
 * 3. **Never anything the app depends on.** Fire-and-forget: it no-ops without a
 *    session (console commands, queued jobs) so callers never guard.
 */
class Analytics
{
    /**
     * Ceiling on queued events. A redirect chain must not grow the session
     * payload without bound; past this we drop silently rather than let
     * measurement cost the request anything.
     */
    private const MAX_QUEUED = 10;

    /**
     * Queue one event for the next full page render.
     *
     * @param  string  $name  snake_case event name
     * @param  array<string, string|int|bool>  $data  low-cardinality properties only — see rule 1 above
     */
    public static function track(string $name, array $data = []): void
    {
        if (! app()->bound('session') || ! session()->isStarted()) {
            return;
        }

        $events = session()->get('analytics', []);

        if (count($events) >= self::MAX_QUEUED) {
            return;
        }

        // The per-entry id lets the browser dedupe: a wire:navigate back
        // restores a cached page snapshot, which re-presents the same payload.
        $events[] = ['id' => uniqid('', true), 'name' => $name, 'data' => $data];

        session()->flash('analytics', $events);
    }

    /**
     * Collapse a count into a bucket label. Used for search-result counts, where
     * the number itself is uninteresting but "none at all" is the whole point: a
     * search that finds no employer is someone telling us which one to add next.
     */
    public static function band(int $count): string
    {
        return match (true) {
            $count <= 0 => '0',
            $count <= 3 => '1-3',
            $count <= 10 => '4-10',
            default => '10+',
        };
    }
}
