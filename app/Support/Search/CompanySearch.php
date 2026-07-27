<?php

namespace App\Support\Search;

use App\Enums\SearchField;
use App\Models\Company;
use App\Models\SearchDocument;
use App\Providers\AppServiceProvider;
use App\Support\Arabic;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Hybrid company search: literal matching and embedding similarity, run
 * together over the same per-field index and fused into one ranked list.
 *
 * Why both legs. Literal search is exact and instant but blind — it cannot
 * connect "شركة برمجيات" to a company whose reviews only ever say "تطوير
 * تطبيقات". Embedding search reads meaning but is fuzzy about names, and will
 * happily rank a plausible neighbour above the company you literally typed.
 * Each leg covers the other's failure, so both run on every query.
 *
 * How a rank is built:
 *   1. every field of every company is scored 0..1 by each leg;
 *   2. the two legs are blended per field (`search.blend`);
 *   3. the field's own weight scales it, which is what makes a name match beat
 *      a comment match of equal strength ({@see SearchField::weight()});
 *   4. a company takes its best field's score, plus a discounted share of the
 *      rest, so matching several fields helps without letting a pile of weak
 *      signals outrank one strong one.
 *
 * Degradation is deliberate: if the embedding provider is slow or down, the
 * semantic leg contributes nothing and search silently continues literal-only.
 * Search never fails because an API did.
 *
 * Bound `scoped` in the container ({@see AppServiceProvider}) so
 * a page that asks for the same query while rendering facet counts and results
 * pays for it once — and so nothing leaks between requests under Octane.
 */
class CompanySearch
{
    /** @var array<string, Collection<int, SearchHit>> */
    protected array $memo = [];

    protected ?bool $indexed = null;

    protected ?int $corpus = null;

    public function __construct(private readonly Embedder $embedder) {}

    /**
     * Rank approved companies against a query, best first.
     *
     * @return Collection<int, SearchHit> keyed by company id
     */
    public function search(?string $term): Collection
    {
        $query = Arabic::normalize($term);

        // The index guard belongs here rather than only in the calling scope:
        // pages read hits directly for the "matched on" line, and that path
        // would otherwise query a table that may not exist yet.
        if ($query === '' || ! $this->hasIndex()) {
            return collect();
        }

        return $this->memo[$query] ??= $this->rank($query);
    }

    /**
     * Matching company ids in rank order — what a query builder constrains on.
     *
     * @return list<int>
     */
    public function ids(?string $term): array
    {
        return $this->search($term)->keys()->all();
    }

    /**
     * Whether there is a usable index to search at all.
     *
     * Both of these are real states on a fresh release: the table is empty
     * because `search:index` has not run yet, or it is missing because a
     * release reached traffic in the window before its migration finished.
     * Neither should take the site down or empty the catalogue — callers fall
     * back to literal name matching, and the missing table is reported so it
     * still gets noticed.
     */
    public function hasIndex(): bool
    {
        if ($this->indexed !== null) {
            return $this->indexed;
        }

        try {
            return $this->indexed = SearchDocument::query()
                ->ofType((new Company)->getMorphClass())
                ->exists();
        } catch (Throwable $exception) {
            report($exception);

            return $this->indexed = false;
        }
    }

    /** @return Collection<int, SearchHit> */
    protected function rank(string $query): Collection
    {
        // Deduplicated because a repeated token would be counted once in the
        // token-weight denominator but twice in the numerator.
        $tokens = array_values(array_unique(preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: []));

        if ($tokens === []) {
            return collect();
        }

        $lexical = $this->lexicalScores($query, $tokens);
        $semantic = $this->semanticScores($query);

        $blend = config('search.blend');
        $secondary = (float) config('search.secondary_field_weight', 0.25);
        $minimum = (float) config('search.min_score', 0.08);

        /** @var array<int, array<string, array{lexical: float, semantic: float, score: float}>> $companies */
        $companies = [];

        foreach ([$lexical, $semantic] as $leg) {
            foreach ($leg as $companyId => $fields) {
                foreach ($fields as $field => $_) {
                    $companies[$companyId][$field] ??= ['lexical' => 0.0, 'semantic' => 0.0, 'score' => 0.0];
                }
            }
        }

        $hits = [];

        foreach ($companies as $companyId => $fields) {
            foreach ($fields as $field => $_) {
                $weight = SearchField::tryFrom($field)?->weight() ?? 0.0;
                $lex = $lexical[$companyId][$field] ?? 0.0;
                $sem = $semantic[$companyId][$field] ?? 0.0;

                $fields[$field] = [
                    'lexical' => round($lex, 4),
                    'semantic' => round($sem, 4),
                    'score' => round(($blend['lexical'] * $lex + $blend['semantic'] * $sem) * $weight, 4),
                ];
            }

            uasort($fields, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

            $scores = array_column($fields, 'score');
            $best = array_shift($scores) ?? 0.0;
            $total = $best + $secondary * array_sum($scores);

            if ($total < $minimum) {
                continue;
            }

            $hits[$companyId] = new SearchHit($companyId, round($total, 4), $fields);
        }

        return collect($hits)
            ->sortByDesc(fn (SearchHit $hit): float => $hit->score)
            ->take((int) config('search.limit', 200));
    }

    /**
     * Literal matching, scored per field.
     *
     * A field scores the better of its whole-phrase match and its weighted
     * average per-token match, so "ارامكو الرياض" rewards a field carrying both
     * words without punishing one that carries the exact phrase.
     *
     * @param  list<string>  $tokens
     * @return array<int, array<string, float>>
     */
    protected function lexicalScores(string $query, array $tokens): array
    {
        $rows = SearchDocument::query()
            ->ofType((new Company)->getMorphClass())
            ->where(function ($builder) use ($tokens): void {
                foreach ($tokens as $token) {
                    $builder->orWhere('content', 'like', '%'.$this->escapeLike($token).'%');
                }
            })
            ->get(['searchable_id', 'field', 'content']);

        $weights = $this->tokenWeights($tokens, $rows);
        $total = array_sum($weights);

        $scores = [];

        foreach ($rows as $row) {
            $content = (string) $row->content;

            $phrase = $this->strength($content, $query);

            $perToken = 0.0;
            foreach ($tokens as $token) {
                $perToken += $this->strength($content, $token) * $weights[$token];
            }
            $perToken = $total > 0.0 ? $perToken / $total : 0.0;

            $score = max($phrase, $perToken);

            if ($score > 0.0) {
                $scores[(int) $row->searchable_id][$row->field->value] = $score;
            }
        }

        return $scores;
    }

    /**
     * What each query token is worth, by how rare it is in the corpus.
     *
     * Without this every token counts the same, and on a real catalogue that
     * ranks badly: a third of the companies here are named "شركة ...", so
     * searching "شركة بترول" scored all of them off the generic word alone and
     * buried the one that actually refines petroleum. This is inverse document
     * frequency — a token carried by most of the corpus says almost nothing
     * about which company you meant, a token carried by two says almost
     * everything — and it is what stops "شركة", "مؤسسة" or "company" from
     * behaving like a real query term without maintaining a stopword list that
     * would need a new entry every time the catalogue grows a habit.
     *
     * Document frequency is read off the rows already fetched instead of
     * re-queried: that set is every row matching any token, so it necessarily
     * contains every occurrence of each one.
     *
     * @param  list<string>  $tokens
     * @param  Collection<int, SearchDocument>  $rows
     * @return array<string, float> keyed by token, each in (0, 1]
     */
    protected function tokenWeights(array $tokens, Collection $rows): array
    {
        $corpus = $this->corpusSize();

        if ($corpus < 1) {
            return array_fill_keys($tokens, 1.0);
        }

        $scale = log(1 + $corpus);
        $weights = [];

        foreach ($tokens as $token) {
            $carrying = $rows
                ->filter(fn (SearchDocument $row): bool => str_contains((string) $row->content, $token))
                ->unique('searchable_id')
                ->count();

            $weights[$token] = log(1 + $corpus / max($carrying, 1)) / $scale;
        }

        return $weights;
    }

    /**
     * How many companies the index holds — the corpus size token weighting is
     * measured against. Memoised per request rather than cached, because the
     * count is one cheap aggregate and a stale N would quietly skew ranking.
     */
    protected function corpusSize(): int
    {
        return $this->corpus ??= SearchDocument::query()
            ->ofType((new Company)->getMorphClass())
            ->distinct()
            ->count('searchable_id');
    }

    /**
     * How strongly a field's text matches one needle, from whole-field equality
     * down to a bare substring.
     */
    protected function strength(string $content, string $needle): float
    {
        $tiers = config('search.lexical');

        if ($content === $needle) {
            return (float) $tiers['exact'];
        }

        if (str_starts_with($content, $needle)) {
            return (float) $tiers['prefix'];
        }

        // Starts a word inside the field — "طيران" in "الخطوط · طيران ناس".
        if (preg_match('/(?:^|\s)'.preg_quote($needle, '/').'/u', $content) === 1) {
            return (float) $tiers['word'];
        }

        return str_contains($content, $needle) ? (float) $tiers['substring'] : 0.0;
    }

    /**
     * Embedding similarity, scored per field.
     *
     * A document is scored by how far its similarity stands out from the rest
     * of *its own field*, not by the raw cosine — because raw cosine is not
     * comparable across fields. Short company names all cluster near each other
     * in vector space (they share "شركة", "مؤسسة", and little else), so an
     * unrelated name reaches a cosine that would be a decisive match among long
     * profile text. Judged on raw value, the name field saturates and every
     * company looks like a perfect semantic hit; judged against its own
     * distribution, the same value is unremarkable.
     *
     * The centre and spread are the median and scaled median absolute
     * deviation rather than mean and standard deviation, so a handful of real
     * matches cannot inflate the baseline they are being measured against.
     *
     * This is deliberately not a min-max over the result set: min-maxing hands
     * the top neighbour a perfect score even when nothing is related, whereas
     * standardising leaves everything near the middle scoring near zero.
     *
     * @return array<int, array<string, float>>
     */
    protected function semanticScores(string $query): array
    {
        $config = config('search.semantic');

        if (mb_strlen($query) < (int) $config['min_query_length']) {
            return [];
        }

        $vector = $this->embedQuery($query);

        if ($vector === null) {
            return [];
        }

        /** @var array<string, list<array{id: int, similarity: float}>> $fields */
        $fields = [];

        foreach ($this->matrix() as $row) {
            $fields[$row['field']][] = [
                'id' => $row['id'],
                'similarity' => Vector::similarity($vector, $row['embedding']),
            ];
        }

        $sample = (int) $config['min_sample'];
        $scores = [];

        foreach ($fields as $field => $rows) {
            [$floor, $ceiling, $values] = count($rows) >= $sample
                ? [(float) $config['deviation_floor'], (float) $config['deviation_ceiling'], $this->deviations($rows)]
                : [(float) $config['floor'], (float) $config['ceiling'], array_column($rows, 'similarity')];

            $span = max($ceiling - $floor, 0.0001);

            foreach ($rows as $index => $row) {
                if ($values[$index] <= $floor) {
                    continue;
                }

                $scores[$row['id']][$field] = min(($values[$index] - $floor) / $span, 1.0);
            }
        }

        return $scores;
    }

    /**
     * Each row's similarity restated as deviations from its field's centre.
     *
     * @param  list<array{id: int, similarity: float}>  $rows
     * @return list<float>
     */
    protected function deviations(array $rows): array
    {
        $similarities = array_column($rows, 'similarity');
        $centre = $this->median($similarities);
        $spread = 1.4826 * $this->median(
            array_map(fn (float $similarity): float => abs($similarity - $centre), $similarities)
        );

        if ($spread <= 1e-9) {
            // More than half a field can share a single value: 244 companies
            // draw on a handful of cities, and the reviewer line repeats across
            // ratings. Median absolute deviation collapses to zero on ties like
            // that, and treating everything above the median as infinitely
            // unusual ranked a medical city first for a query about web
            // development. Standard deviation is the weaker estimator in
            // general and the only usable one here, because ties do not break it.
            $spread = $this->standardDeviation($similarities);
        }

        if ($spread <= 1e-9) {
            // Every document scores identically, so nothing distinguishes
            // anything and the field has no opinion to contribute.
            return array_fill(0, count($similarities), 0.0);
        }

        return array_map(
            fn (float $similarity): float => ($similarity - $centre) / $spread,
            $similarities
        );
    }

    /**
     * @param  list<float>  $values
     */
    protected function standardDeviation(array $values): float
    {
        $count = count($values);

        if ($count === 0) {
            return 0.0;
        }

        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(fn (float $value): float => ($value - $mean) ** 2, $values)) / $count;

        return sqrt($variance);
    }

    /**
     * @param  list<float>  $values
     */
    protected function median(array $values): float
    {
        if ($values === []) {
            return 0.0;
        }

        sort($values);
        $middle = intdiv(count($values), 2);

        return count($values) % 2 === 1
            ? $values[$middle]
            : ($values[$middle - 1] + $values[$middle]) / 2;
    }

    /**
     * Embed the query, cached — live search re-sends the same prefixes on every
     * keystroke, and each miss is a paid round-trip on the render path.
     *
     * @return list<float>|null null when the provider is unavailable, which
     *                          drops the semantic leg instead of failing the search.
     */
    protected function embedQuery(string $query): ?array
    {
        $key = 'search:query:'.$this->embedder->model().':'.$this->embedder->dimensions().':'.md5($query);

        try {
            $encoded = Cache::remember(
                $key,
                (int) config('search.cache.query_ttl', 86400),
                fn (): string => Vector::encode($this->embedder->embed([$query])[0] ?? [])
            );
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }

        $vector = Vector::decode($encoded);

        return $vector === [] ? null : $vector;
    }

    /**
     * Every embedded field vector, cached whole.
     *
     * The cache key carries the index's own row count and newest timestamp, so
     * re-indexing invalidates it without anything having to remember to flush.
     *
     * @return list<array{id: int, field: string, embedding: string}>
     */
    protected function matrix(): array
    {
        $type = (new Company)->getMorphClass();

        $stamp = DB::table('search_documents')
            ->where('searchable_type', $type)
            ->whereNotNull('embedding')
            ->selectRaw('count(*) as rows, max(indexed_at) as newest')
            ->first();

        $version = md5(($stamp->rows ?? 0).'|'.($stamp->newest ?? '').'|'.$this->embedder->dimensions());

        return Cache::remember(
            "search:matrix:{$version}",
            (int) config('search.cache.matrix_ttl', 3600),
            fn (): array => SearchDocument::query()
                ->ofType($type)
                ->whereNotNull('embedding')
                ->get(['searchable_id', 'field', 'embedding'])
                ->map(fn (SearchDocument $document): array => [
                    'id' => (int) $document->searchable_id,
                    'field' => $document->field->value,
                    'embedding' => (string) $document->embedding,
                ])
                ->all()
        );
    }

    /** LIKE wildcards inside a user's query are literal characters, not operators. */
    protected function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
