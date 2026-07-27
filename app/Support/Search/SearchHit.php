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
     * The fields that actually carried the match, strongest first — the
     * "matched on" line.
     *
     * Two rules keep this an explanation instead of a dump. The best field is
     * always listed, even if weak: a result with no stated reason reads as a
     * bug. Everything after it must score within `$relative` of that best
     * field, because a semantic query gives *every* field some score, and
     * listing all six says nothing about why this result is here.
     *
     * @return list<SearchField>
     */
    public function matchedFields(float $relative = 0.55, int $limit = 3): array
    {
        $matched = [];
        $best = null;

        foreach ($this->fields as $key => $scores) {
            $field = SearchField::tryFrom($key);

            if ($field === null) {
                continue;
            }

            if ($best === null) {
                $best = $scores['score'];
                $matched[] = $field;

                continue;
            }

            if (count($matched) >= $limit || $scores['score'] < $best * $relative) {
                break;
            }

            $matched[] = $field;
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
