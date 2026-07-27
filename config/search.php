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
         * Cosine similarity is mapped affinely onto 0..1 through this window,
         * so scores stay comparable with the lexical leg run to run. Below
         * `floor` a document contributes nothing — that is what stops semantic
         * search from dragging in loosely-related noise.
         *
         * These are measured, not guessed. Against this corpus, this model
         * scores a genuinely relevant Arabic field at roughly 0.38–0.55 and an
         * unrelated one at around 0.22 — cosine never approaches 1.0 outside a
         * near-verbatim match. A window anchored near 1.0 would flatten every
         * real match to nearly nothing, which is exactly how a semantic leg
         * ends up silently contributing zero. Re-measure before changing.
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
