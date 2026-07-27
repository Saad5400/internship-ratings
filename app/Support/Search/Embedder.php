<?php

namespace App\Support\Search;

/**
 * A pluggable embedding backend.
 *
 * Nothing in the search stack names a provider directly — it depends on this
 * contract, and `config/search.php` picks the driver. Swapping OpenRouter for
 * another provider later is a new class here, not a caller change.
 */
interface Embedder
{
    /**
     * Embed a batch of strings into unit-length vectors, index-aligned with the input.
     *
     * @param  list<string>  $texts
     * @return list<list<float>>
     */
    public function embed(array $texts): array;

    /** The vector dimension every returned embedding has. */
    public function dimensions(): int;

    /** Identifier of the model that produced the vectors, stored alongside them. */
    public function model(): string;
}
