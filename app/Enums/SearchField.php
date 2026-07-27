<?php

namespace App\Enums;

use App\Support\Search\CompanyDocument;

/**
 * The weighted fields a company is searchable by.
 *
 * Search indexes and scores each field separately: a query is matched against
 * every field, and the field's `weight()` scales the result. That ordering is
 * the product decision — a hit on the company's own name should always outrank
 * the same hit buried in a review comment.
 *
 * Adding a field means adding a case here plus its extractor in
 * {@see CompanyDocument}; nothing else needs to change.
 */
enum SearchField: string
{
    case Name = 'name';
    case City = 'city';
    case Profile = 'profile';
    case Role = 'role';
    case Reviewer = 'reviewer';
    case Comments = 'comments';

    /** Arabic label, shown on a result to explain why it matched. */
    public function label(): string
    {
        return match ($this) {
            self::Name => 'اسم الجهة',
            self::City => 'المدينة',
            self::Profile => 'نبذة الجهة',
            self::Role => 'المسمّى الوظيفي',
            self::Reviewer => 'بيانات المتدرّب',
            self::Comments => 'التعليقات',
        };
    }

    /**
     * Ranking weight, where the company name is the 1.0 benchmark. Comments
     * sit lowest on purpose: they are the largest, noisiest text in the index,
     * so they should surface a company only when nothing stronger matches.
     */
    public function weight(): float
    {
        return match ($this) {
            self::Name => 1.0,
            self::City => 0.7,
            self::Profile => 0.6,
            self::Role => 0.5,
            self::Reviewer => 0.3,
            self::Comments => 0.2,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Cases ordered by descending weight — the order results explain themselves in. */
    public static function byWeight(): array
    {
        $cases = self::cases();

        usort($cases, fn (self $a, self $b): int => $b->weight() <=> $a->weight());

        return $cases;
    }
}
