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
     * Deploys do not run migrations here, so both of these are real states on
     * a fresh release: the table is missing entirely, or it exists and is
     * empty because `search:index` has not run yet. Neither should take the
     * site down or empty the catalogue — callers fall back to literal name
     * matching, and the missing table is reported so it still gets noticed.
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
        $tokens = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];

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
     * A field scores the better of its whole-phrase match and its average
     * per-token match, so "ارامكو الرياض" rewards a field carrying both words
     * without punishing one that carries the exact phrase.
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

        $scores = [];

        foreach ($rows as $row) {
            $content = (string) $row->content;

            $phrase = $this->strength($content, $query);

            $perToken = 0.0;
            foreach ($tokens as $token) {
                $perToken += $this->strength($content, $token);
            }
            $perToken /= count($tokens);

            $score = max($phrase, $perToken);

            if ($score > 0.0) {
                $scores[(int) $row->searchable_id][$row->field->value] = $score;
            }
        }

        return $scores;
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
     * Cosine values are mapped through a fixed window rather than min-maxed
     * across the result set: min-maxing would hand the top neighbour a perfect
     * score even when nothing in the corpus is actually related.
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

        $floor = (float) $config['floor'];
        $ceiling = (float) $config['ceiling'];
        $span = max($ceiling - $floor, 0.0001);

        $scores = [];

        foreach ($this->matrix() as $row) {
            $similarity = Vector::similarity($vector, $row['embedding']);

            if ($similarity <= $floor) {
                continue;
            }

            $scores[$row['id']][$row['field']] = min(($similarity - $floor) / $span, 1.0);
        }

        return $scores;
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
