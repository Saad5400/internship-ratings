<?php

namespace App\Support;

use App\Models\Company;
use App\Models\PopularSearch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * "People searched for" — the single owner of search-term popularity.
 *
 * PRIVACY: nothing here ever touches a user id, session id, IP address, user
 * agent or per-search timestamp. One row per distinct term, an integer counter,
 * and an hour-granular `last_searched_at` used only to age terms out. There is
 * deliberately no way to ask "who searched this" because the data to answer it
 * is never written. See the `popular_searches` migration for the full contract.
 *
 * Terms are keyed by {@see Arabic::normalize} — the single owner of Arabic
 * normalization — so "الإنماء" and "الانماء" increment the same counter.
 */
final class PopularSearches
{
    /**
     * Shortest term worth counting. Below this a term is a keystroke, not a
     * search, and one or two Arabic letters describe nothing.
     */
    public const MIN_TERM_LENGTH = 3;

    /**
     * Longest term worth counting. A chip has to fit on one line, and a pasted
     * paragraph is not a search anyone repeats — so over-long terms are dropped
     * at write time rather than truncated (truncating would silently merge
     * distinct queries into one counter).
     */
    public const MAX_TERM_LENGTH = 40;

    /**
     * Searches a term needs before it is shown. Above 1 for two reasons: a term
     * searched once is not "popular", and surfacing it would broadcast one
     * visitor's private query to everyone. Five is high enough that it is not
     * an accident and low enough that a real trend clears it in a day.
     */
    public const MIN_COUNT = 5;

    /** How far back a term still counts as something people are searching for. */
    public const RECENT_DAYS = 30;

    /** Short enough that a genuinely trending term appears within minutes. */
    public const CACHE_TTL = 300;

    public const CACHE_KEY = 'popular-searches-top';

    /**
     * Count one *settled* search.
     *
     * Call this once per search the visitor actually meant — after the input
     * has settled (debounced) or on submit — never per keystroke: every
     * keystroke would count its own prefix and the counts would describe typing
     * rather than intent.
     *
     * Never throws. Recording is bookkeeping attached to search; a failure here
     * must not take search itself down, so everything is swallowed and reported.
     */
    public static function record(?string $term): void
    {
        try {
            $normalized = Arabic::normalize($term);
            $display = self::displayForm($term);

            if (! self::isCountable($normalized) || $display === '') {
                return;
            }

            if (self::increment($normalized) > 0) {
                return;
            }

            try {
                PopularSearch::query()->create([
                    'term' => $normalized,
                    'display_term' => $display,
                    'search_count' => 1,
                    'last_searched_at' => self::coarseNow(),
                ]);
            } catch (Throwable) {
                // Lost the race to a concurrent first search of the same term;
                // the row exists now, so just count against it.
                self::increment($normalized);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * The most-searched recent terms, most popular first, ready to render.
     *
     * Cached for {@see CACHE_TTL} seconds — this is decoration on a hot page,
     * not a live counter.
     *
     * Never throws, for the same reason {@see record()} does not. This renders
     * on the public companies index, and a release can reach traffic before its
     * migration lands — `start.sh` runs `migrate --force || true`, so a failed
     * migration boots the app anyway. Suggestion chips are a nicety; losing them
     * is invisible, while letting them raise would take the catalogue down.
     *
     * @return list<string>
     */
    public static function top(int $limit = 5): array
    {
        $limit = max(0, min($limit, 20));

        if ($limit === 0) {
            return [];
        }

        try {
            return Cache::remember(
                self::CACHE_KEY.':'.$limit,
                self::CACHE_TTL,
                fn (): array => self::query($limit),
            );
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }
    }

    /**
     * Forget the cached lists. Only needed when a term is removed out of band
     * (moderation); ordinary staleness is handled by the TTL.
     */
    public static function flush(): void
    {
        for ($limit = 1; $limit <= 20; $limit++) {
            Cache::forget(self::CACHE_KEY.':'.$limit);
        }
    }

    /**
     * Candidates in popularity order, filtered down to terms that still lead
     * somewhere.
     *
     * More candidates are read than returned because the "leads somewhere"
     * check can only be applied per term; the over-fetch keeps one dead term
     * from shortening the list.
     *
     * @return list<string>
     */
    private static function query(int $limit): array
    {
        $candidates = PopularSearch::query()
            ->surfaceable()
            ->orderByDesc('search_count')
            ->orderBy('term')
            ->limit($limit * 4)
            ->get(['term', 'display_term']);

        $terms = [];

        foreach ($candidates as $candidate) {
            // Re-applied on read as well as on write, so a row stored under
            // older thresholds can never surface as junk.
            $display = self::displayForm($candidate->display_term);

            if ($display === '' || ! self::isCountable($candidate->term)) {
                continue;
            }

            if (! self::leadsSomewhere($candidate->term)) {
                continue;
            }

            $terms[] = $display;

            if (count($terms) >= $limit) {
                break;
            }
        }

        return $terms;
    }

    /**
     * Whether the term actually finds an approved company.
     *
     * This is the main defence against a visitor repeat-searching a slur to get
     * it rendered as a chip: an injected string matches no company, so it never
     * surfaces however often it is submitted. Name matching goes through
     * {@see Company::scopeSearchByName} rather than the hybrid engine because
     * this runs on a cache miss and hybrid search is a paid network call.
     */
    private static function leadsSomewhere(string $term): bool
    {
        return Company::approved()->searchByName($term)->exists();
    }

    /** Increment in one statement; returns the number of rows it touched. */
    private static function increment(string $normalized): int
    {
        return PopularSearch::query()
            ->where('term', $normalized)
            ->update([
                'search_count' => DB::raw('search_count + 1'),
                'last_searched_at' => self::coarseNow(),
            ]);
    }

    private static function isCountable(string $normalized): bool
    {
        $length = mb_strlen($normalized);

        return $length >= self::MIN_TERM_LENGTH && $length <= self::MAX_TERM_LENGTH;
    }

    /**
     * The readable form kept for display: whitespace collapsed, control
     * characters and bidirectional overrides stripped — those last are a real
     * hazard in an RTL interface, where an embedded override can reorder the
     * text around a chip.
     */
    private static function displayForm(?string $term): string
    {
        if ($term === null) {
            return '';
        }

        $term = preg_replace('/[\p{C}]/u', '', $term) ?? '';
        $term = preg_replace('/\s+/u', ' ', trim($term)) ?? '';

        return mb_strlen($term) > self::MAX_TERM_LENGTH ? '' : $term;
    }

    /**
     * Now, truncated to the hour. Storing the exact minute of a search would
     * reintroduce the correlation this table exists to avoid.
     */
    private static function coarseNow(): string
    {
        return now()->startOfHour()->toDateTimeString();
    }
}
