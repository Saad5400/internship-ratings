<?php

namespace App\Support\Search;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * The real embedding driver — OpenRouter's OpenAI-compatible embeddings
 * endpoint, the same one the Catodemy corpus index uses.
 *
 * Octane-safe: every call re-reads its inputs from the constructor-injected
 * config and holds no per-request state.
 */
class OpenRouterEmbedder implements Embedder
{
    public function __construct(
        private readonly string $model,
        private readonly int $dimensions,
        private readonly string $apiKey,
        private readonly string $baseUrl = 'https://openrouter.ai/api/v1',
        private readonly int $timeout = 8,
    ) {}

    public function embed(array $texts): array
    {
        if ($texts === []) {
            return [];
        }

        if ($this->apiKey === '') {
            throw new RuntimeException(
                'OPENROUTER_API_KEY is not set — cannot embed search text. '
                .'Set the key, or set SEARCH_EMBEDDING_DRIVER=fake outside production.'
            );
        }

        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->retry(2, 250, throw: false)
            ->post(rtrim($this->baseUrl, '/').'/embeddings', [
                'model' => $this->model,
                'input' => array_values($texts),
                'dimensions' => $this->dimensions,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                "OpenRouter embeddings request failed (HTTP {$response->status()}): ".$response->body()
            );
        }

        $rows = $response->json('data');

        if (! is_array($rows) || count($rows) !== count($texts)) {
            throw new RuntimeException(
                'OpenRouter embeddings response shape unexpected: expected '
                .count($texts).' vectors, got '.(is_array($rows) ? count($rows) : 'none').'.'
            );
        }

        // Preserve request order via each row's `index` when the provider sends one.
        $ordered = [];

        foreach ($rows as $position => $row) {
            $index = is_array($row) && isset($row['index']) ? (int) $row['index'] : $position;
            $embedding = is_array($row) ? ($row['embedding'] ?? null) : null;

            if (! is_array($embedding)) {
                throw new RuntimeException('OpenRouter embeddings response missing an embedding vector.');
            }

            $ordered[$index] = Vector::normalize(array_map(static fn ($value): float => (float) $value, $embedding));
        }

        ksort($ordered);

        return array_values($ordered);
    }

    public function dimensions(): int
    {
        return $this->dimensions;
    }

    public function model(): string
    {
        return $this->model;
    }
}
