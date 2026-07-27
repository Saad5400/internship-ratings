<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Embedding provider (the swap seam)
    |--------------------------------------------------------------------------
    |
    | The ONLY place a concrete embedding provider is named. Callers depend on
    | App\Support\Search\Embedder; App\Providers\AppServiceProvider picks the
    | driver from here. `fake` is a deterministic, network-free driver used by
    | the test suite and by local work without a key — it produces stable
    | vectors, so semantic assertions stay reproducible.
    |
    | `model` matches the Catodemy corpus index (openai/text-embedding-3-small)
    | so both apps share one embedding vocabulary. `dimensions` deliberately
    | does NOT: this index stores one vector per *field* per company, so it
    | truncates the model's 1536-dim output to keep the whole matrix small
    | enough to score in PHP on every keystroke. text-embedding-3-small is a
    | Matryoshka model — truncating is supported and the API renormalizes.
    |
    */

    'embeddings' => [
        'driver' => env('SEARCH_EMBEDDING_DRIVER', 'openrouter'),
        'key' => env('OPENROUTER_API_KEY'),
        'url' => env('OPENROUTER_URL', 'https://openrouter.ai/api/v1'),
        'model' => env('SEARCH_EMBEDDING_MODEL', 'openai/text-embedding-3-small'),
        'dimensions' => (int) env('SEARCH_EMBEDDING_DIMENSIONS', 512),

        /** Texts per embeddings request when re-indexing in bulk. */
        'batch' => (int) env('SEARCH_EMBEDDING_BATCH', 64),

        /** Seconds. Kept short on the query path so a slow provider degrades to lexical-only. */
        'timeout' => (int) env('SEARCH_EMBEDDING_TIMEOUT', 8),

        /** Longest field text sent to the provider — caps cost and keeps rows small. */
        'max_chars' => (int) env('SEARCH_EMBEDDING_MAX_CHARS', 2000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Hybrid ranking
    |--------------------------------------------------------------------------
    |
    | Every result carries a lexical score and a semantic score per field. The
    | two legs are blended per field, the field's own weight
    | (App\Enums\SearchField::weight()) scales it, and the best-scoring field
    | wins — which is what makes a name match outrank a comment match of the
    | same strength.
    |
    */

    'blend' => [
        'lexical' => (float) env('SEARCH_BLEND_LEXICAL', 0.6),
        'semantic' => (float) env('SEARCH_BLEND_SEMANTIC', 0.4),
    ],

    'lexical' => [
        /** Match strengths, strongest first. A field scores its best hit per query term. */
        'exact' => 1.0,
        'prefix' => 0.85,
        'word' => 0.7,
        'substring' => 0.4,
    ],

    'semantic' => [
        /**
         * How far above a field's own typical similarity a document must sit
         * before it counts, measured in robust standard deviations (median
         * absolute deviation, scaled). Below `deviation_floor` a document
         * contributes nothing; at `deviation_ceiling` it scores a full 1.0.
         *
         * These are deviations rather than raw cosine because raw cosine is not
         * comparable across fields, and a single window over all of them ranks
         * nonsense. Measured against this corpus: querying "شركة بترول" scores
         * the *name* of شركة المياه الوطنية — a water utility — at cosine 0.559,
         * while querying "مصرف وبنك" scores the name of بنك الجزيرة at 0.555.
         * Identical similarity, one pure noise and one exactly right, so no
         * fixed cutoff can separate them. The difference only appears against
         * each field's own distribution: short names cluster high and long
         * profile text clusters low, so the same 0.55 is unremarkable among
         * names and a strong outlier among profiles.
         *
         * Standardised, they separate: on that same corpus the بترول name-field
         * noise peaks at 3.3 deviations while أرامكو's profile reaches 5.1, and
         * every genuine bank match lands between 3.3 and 5.9. Hence a floor just
         * above the noise and a ceiling at the top of the observed signal.
         *
         * Re-measure before changing — but note these are scale-free, so unlike
         * raw cosine they should survive a change of embedding model.
         */
        'deviation_floor' => (float) env('SEARCH_SEMANTIC_DEVIATION_FLOOR', 3.0),
        'deviation_ceiling' => (float) env('SEARCH_SEMANTIC_DEVIATION_CEILING', 6.0),

        /**
         * Documents a field needs before its distribution is worth measuring.
         * A median and deviation taken over three companies describe those
         * three companies, not the corpus — standardising against them would
         * put a genuine match half a deviation from the centre and score it
         * zero. Below this count the absolute window is used instead, which is
         * the right tool when there is not yet a distribution to compare to:
         * a small catalogue (a fresh install, a seeded test) is exactly where
         * "is this similar at all" beats "is this unusually similar".
         */
        'min_sample' => (int) env('SEARCH_SEMANTIC_MIN_SAMPLE', 20),

        /**
         * The absolute cosine window, used only below `min_sample`. Measured
         * against long-form Arabic field text: a genuinely relevant field
         * scores roughly 0.38–0.55 and an unrelated one around 0.22.
         */
        'floor' => (float) env('SEARCH_SEMANTIC_FLOOR', 0.26),
        'ceiling' => (float) env('SEARCH_SEMANTIC_CEILING', 0.58),

        /** Queries shorter than this skip the provider round-trip entirely. */
        'min_query_length' => (int) env('SEARCH_SEMANTIC_MIN_LENGTH', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Result shaping
    |--------------------------------------------------------------------------
    |
    | `min_score` drops results that neither leg really matched, so a search
    | returns "nothing found" instead of a page of weak semantic neighbours.
    |
    */

    'min_score' => (float) env('SEARCH_MIN_SCORE', 0.04),
    'limit' => (int) env('SEARCH_LIMIT', 200),

    /**
     * How much a company's non-best fields add to its rank. Matching the name
     * *and* the city should beat matching the name alone — but not by enough
     * that a company matching five weak fields overtakes an exact name match.
     */
    'secondary_field_weight' => (float) env('SEARCH_SECONDARY_FIELD_WEIGHT', 0.25),

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Query embeddings are cached because live search re-sends the same prefixes
    | constantly. The vector matrix is cached whole and versioned by the index's
    | own row count + newest `indexed_at`, so re-indexing invalidates it without
    | an explicit flush.
    |
    | SCALE NOTE: the matrix is scored in PHP, which is the right trade at this
    | corpus size (hundreds of companies, ~1.5k field vectors ≈ 4MB). If the
    | corpus grows past ~10k vectors, move the semantic leg to pgvector on the
    | production Postgres connection — the Embedder contract and the document
    | table stay as they are.
    |
    */

    'cache' => [
        'query_ttl' => (int) env('SEARCH_QUERY_CACHE_TTL', 86400),
        'matrix_ttl' => (int) env('SEARCH_MATRIX_CACHE_TTL', 3600),
    ],

];
