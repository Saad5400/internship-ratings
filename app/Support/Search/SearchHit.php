<?php

namespace App\Support\Search;

use App\Enums\SearchField;

/**
 * One ranked company, with the per-field scores that produced its rank.
 *
 * The breakdown is kept rather than collapsed to a single number so results can
 * explain themselves ("matched on: city") and so ranking stays debuggable —
 * a search that feels wrong can be read field by field.
 */
final readonly class SearchHit
{
    /**
     * @param  array<string, array{lexical: float, semantic: float, score: float}>  $fields
     *                                                                                       Keyed by {@see SearchField} value, ordered by descending score.
     */
    public function __construct(
        public int $companyId,
        public float $score,
        public array $fields,
    ) {}

    /** The field that contributed most — what the result matched "on". */
    public function topField(): ?SearchField
    {
        $key = array_key_first($this->fields);

        return $key === null ? null : SearchField::tryFrom($key);
    }

    /**
     * Fields that meaningfully contributed, strongest first. Used for the
     * "matched on" line; a long tail of near-zero fields is noise, not an
     * explanation.
     *
     * The best field is always included even if it falls under the threshold —
     * a result in the list with no stated reason reads as a bug, and a weak
     * match still has a strongest field.
     *
     * @return list<SearchField>
     */
    public function matchedFields(float $threshold = 0.05): array
    {
        $matched = [];

        foreach ($this->fields as $key => $scores) {
            if ($scores['score'] < $threshold && $matched !== []) {
                continue;
            }

            if ($field = SearchField::tryFrom($key)) {
                $matched[] = $field;
            }
        }

        return $matched;
    }

    /**
     * Whether the semantic leg — not a literal text match — is what surfaced
     * this. Drives the "matched by meaning" hint, since a result with no
     * visible text overlap otherwise looks like a bug to the reader.
     *
     * Indexes rather than `reset()`: that takes its array by reference, which
     * PHP refuses on a readonly property.
     */
    public function isSemanticMatch(): bool
    {
        $key = array_key_first($this->fields);

        if ($key === null) {
            return false;
        }

        return $this->fields[$key]['semantic'] > $this->fields[$key]['lexical'];
    }
}
