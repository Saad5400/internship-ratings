<?php

namespace App\Support\Search;

use App\Support\Arabic;

/**
 * Deterministic, network-free embedder.
 *
 * Used by the test suite and by local work without an OpenRouter key. It hashes
 * normalized tokens into buckets, so texts that share vocabulary land close
 * together and the pipeline can be exercised end to end — but it models no real
 * meaning. A test that needs true paraphrase behaviour should bind its own
 * {@see Embedder} stub with hand-authored vectors instead.
 */
class FakeEmbedder implements Embedder
{
    public function __construct(private readonly int $dimensions = 512) {}

    public function embed(array $texts): array
    {
        return array_map(fn (string $text): array => $this->vectorFor($text), array_values($texts));
    }

    public function dimensions(): int
    {
        return $this->dimensions;
    }

    public function model(): string
    {
        return 'fake';
    }

    /** @return list<float> */
    protected function vectorFor(string $text): array
    {
        $vector = array_fill(0, $this->dimensions, 0.0);
        $tokens = preg_split('/\s+/u', Arabic::normalize($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($tokens as $token) {
            $digest = crc32($token);
            $vector[$digest % $this->dimensions] += 1.0;
            // A second bucket per token keeps short texts from colliding wholesale.
            $vector[intdiv($digest, $this->dimensions) % $this->dimensions] += 0.5;
        }

        return Vector::normalize($vector);
    }
}
