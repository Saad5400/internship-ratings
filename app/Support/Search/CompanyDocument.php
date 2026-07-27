<?php

namespace App\Support\Search;

use App\Enums\SearchField;
use App\Models\Company;
use App\Models\Rating;
use App\Support\Arabic;
use Illuminate\Support\Collection;

/**
 * Turns a company into the weighted field texts the search index stores.
 *
 * A company's searchable surface is wider than its own row: the city, role and
 * comment fields are aggregated from its *approved* ratings, so a company
 * becomes findable by where people actually trained and what they said — while
 * unapproved content stays out of the public index entirely.
 *
 * Every field is stored through {@see Arabic::normalize}, so the lexical leg
 * matches the same text the semantic leg was embedded from, and Arabic letter
 * variants never split a match.
 */
final class CompanyDocument
{
    /**
     * Field texts keyed by {@see SearchField} value. Fields with nothing to say
     * are absent rather than empty — an empty field would cost an embedding
     * call and could only ever score zero.
     *
     * @return array<string, string>
     */
    public static function build(Company $company): array
    {
        $ratings = $company->relationLoaded('approvedRatings')
            ? $company->approvedRatings
            : $company->approvedRatings()->get();

        $fields = [
            SearchField::Name->value => $company->name,
            SearchField::City->value => self::distinct($ratings, ['city']),
            SearchField::Profile->value => self::profile($company),
            SearchField::Role->value => self::distinct($ratings, ['role_title', 'department']),
            SearchField::Reviewer->value => self::distinct($ratings, [
                'reviewer_university', 'reviewer_college', 'reviewer_major',
            ]),
            SearchField::Comments->value => self::distinct($ratings, ['review_text', 'pros', 'cons']),
        ];

        $limit = (int) config('search.embeddings.max_chars', 2000);

        return collect($fields)
            ->map(fn (string $text): string => mb_substr(Arabic::normalize($text), 0, $limit))
            ->reject(fn (string $text): bool => $text === '')
            ->all();
    }

    /** The company's own descriptive text: what it is, plus its own words about itself. */
    protected static function profile(Company $company): string
    {
        $host = filled($company->website)
            ? (parse_url($company->website, PHP_URL_HOST) ?: $company->website)
            : null;

        return self::join([
            $company->type?->label(),
            $company->description,
            $host,
        ]);
    }

    /**
     * Collect one or more rating columns across a company's ratings, de-duplicated.
     *
     * De-duplication is what keeps this honest: fifty ratings from Riyadh
     * should make a company *findable* by Riyadh, not fifty times more relevant
     * than a company with one.
     *
     * @param  Collection<int, Rating>  $ratings
     * @param  list<string>  $columns
     */
    protected static function distinct(Collection $ratings, array $columns): string
    {
        return self::join(
            $ratings
                ->flatMap(fn (Rating $rating): array => array_map(
                    fn (string $column) => $rating->getAttribute($column),
                    $columns
                ))
                ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
                ->map(fn (string $value): string => trim($value))
                ->unique()
                ->all()
        );
    }

    /** @param  array<int, string|null>  $parts */
    protected static function join(array $parts): string
    {
        return implode(' · ', array_filter($parts, fn (?string $part): bool => filled($part)));
    }
}
