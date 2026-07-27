<?php

namespace App\Support;

use App\Enums\CompanyType;
use App\Enums\Modality;
use App\Enums\Recommendation;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for the public companies index filters.
 *
 * A facet is one filterable dimension (city, company type, …). Values inside a
 * facet are OR-ed, facets are AND-ed together. Every facet sourced from
 * `ratings` must be satisfied by the *same* approved rating, so "Jeddah +
 * remote" means one rating that is both, not one rating per condition.
 *
 * Option lists are never hardcoded: they are derived from the rows that survive
 * every *other* active facet, so an option is only ever offered when picking it
 * would return results.
 */
final class CompanyFacets
{
    /**
     * Sentinel for "the reviewer didn't record this" — a first-class, pickable
     * option rather than a hidden hole in the data. Maps to `IS NULL`.
     */
    public const NONE = '__none__';

    /**
     * Facets in descending order of importance; the UI renders them in this
     * order and reveals the tail behind a "more filters" toggle.
     *
     * `expression` is raw SQL evaluated against a row of the facet's source
     * table. `order` fixes the option order (null = most common first).
     *
     * @return array<string, array{label: string, source: 'company'|'rating', expression: string, order: list<string>|null, searchable: bool}>
     */
    public static function definitions(): array
    {
        return [
            'city' => [
                'label' => 'المدينة',
                'source' => 'rating',
                'expression' => "nullif(trim(ratings.city), '')",
                'order' => null,
                'searchable' => true,
            ],
            'type' => [
                'label' => 'نوع الجهة',
                'source' => 'company',
                'expression' => "nullif(trim(companies.type), '')",
                'order' => CompanyType::values(),
                'searchable' => false,
            ],
            'modality' => [
                'label' => 'نمط العمل',
                'source' => 'rating',
                'expression' => "nullif(trim(ratings.modality), '')",
                'order' => Modality::values(),
                'searchable' => false,
            ],
            'stipend' => [
                'label' => 'المكافأة',
                'source' => 'rating',
                'expression' => "case when ratings.stipend_sar > 0 then 'paid' else 'unpaid' end",
                'order' => ['paid', 'unpaid'],
                'searchable' => false,
            ],
            'recommendation' => [
                'label' => 'التوصية',
                'source' => 'rating',
                'expression' => "nullif(trim(ratings.recommendation), '')",
                'order' => Recommendation::values(),
                'searchable' => false,
            ],
            'duration' => [
                'label' => 'مدة التدريب',
                'source' => 'rating',
                'expression' => 'case '
                    .'when ratings.duration_months is null then null '
                    .'when ratings.duration_months <= 2 then \'short\' '
                    .'when ratings.duration_months <= 4 then \'medium\' '
                    .'else \'long\' end',
                'order' => ['short', 'medium', 'long'],
                'searchable' => false,
            ],
        ];
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::definitions());
    }

    public static function label(string $facet): string
    {
        return self::definitions()[$facet]['label'] ?? $facet;
    }

    /**
     * The Arabic label for a stored facet value. Cities store their own display
     * text; everything else routes through the owning enum so labels are never
     * re-mapped here.
     */
    public static function optionLabel(string $facet, string $value): string
    {
        if ($value === self::NONE) {
            return 'غير محدد';
        }

        return match ($facet) {
            'type' => CompanyType::tryFrom($value)?->label() ?? $value,
            'modality' => Modality::tryFrom($value)?->label() ?? $value,
            'recommendation' => Recommendation::tryFrom($value)?->label() ?? $value,
            'stipend' => $value === 'paid' ? 'بمكافأة' : 'بدون مكافأة',
            'duration' => match ($value) {
                'short' => 'شهران أو أقل',
                'medium' => '3 - 4 أشهر',
                'long' => '5 أشهر فأكثر',
                default => $value,
            },
            default => $value,
        };
    }

    /**
     * Drop unknown facets, non-string values and duplicates so a hand-edited
     * query string can never reach the query builder.
     *
     * @param  mixed  $filters
     * @return array<string, list<string>>
     */
    public static function sanitize($filters): array
    {
        if (! is_array($filters)) {
            return [];
        }

        $clean = [];

        foreach (self::keys() as $facet) {
            $values = $filters[$facet] ?? null;

            if (! is_array($values)) {
                continue;
            }

            $values = array_values(array_unique(array_filter(
                $values,
                fn ($value): bool => is_string($value) && $value !== ''
            )));

            if ($values !== []) {
                $clean[$facet] = $values;
            }
        }

        return $clean;
    }

    /**
     * Apply every selected facet to a companies query.
     *
     * @param  array<string, list<string>>  $filters
     * @param  string|null  $except  Facet to leave out, used when counting that facet's own options.
     */
    public static function apply(Builder $query, array $filters, ?string $except = null): Builder
    {
        $filters = self::sanitize($filters);

        foreach (self::definitions() as $facet => $definition) {
            if ($facet === $except || $definition['source'] !== 'company' || ! isset($filters[$facet])) {
                continue;
            }

            self::constrain($query, $definition['expression'], $filters[$facet]);
        }

        $ratingFilters = self::ratingFilters($filters, $except);

        if ($ratingFilters !== []) {
            $query->whereHas('approvedRatings', function (Builder $ratings) use ($ratingFilters): void {
                foreach ($ratingFilters as $expression => $values) {
                    self::constrain($ratings, $expression, $values);
                }
            });
        }

        return $query;
    }

    /**
     * Option lists with live company counts for every facet, computed against
     * the current search and the other active facets. Options that would return
     * nothing are absent by construction — they simply never come back from the
     * grouped count.
     *
     * @param  array<string, list<string>>  $filters
     * @return array<string, list<array{value: string, label: string, count: int}>>
     */
    public static function options(?string $search, array $filters): array
    {
        $filters = self::sanitize($filters);
        $options = [];

        foreach (self::definitions() as $facet => $definition) {
            $options[$facet] = self::sortOptions(
                $facet,
                $definition['order'],
                self::counts($facet, $definition, $search, $filters)
            );
        }

        return $options;
    }

    /**
     * @param  array{label: string, source: 'company'|'rating', expression: string, order: list<string>|null, searchable: bool}  $definition
     * @param  array<string, list<string>>  $filters
     * @return array<string, int> facet value => number of matching companies
     */
    protected static function counts(string $facet, array $definition, ?string $search, array $filters): array
    {
        $expression = $definition['expression'];

        // Same entry point the results list uses, so a chip's count always
        // describes the list the user is looking at.
        $query = Company::approved()->matchingSearch($search);

        if ($definition['source'] === 'company') {
            self::apply($query, $filters, except: $facet);

            $rows = $query->toBase()
                ->selectRaw("{$expression} as facet_value, count(*) as aggregate")
                ->groupBy(DB::raw($expression))
                ->get();
        } else {
            // Joining (instead of `whereHas`) is what enforces same-rating
            // semantics: the row being grouped is the row that had to satisfy
            // every other rating-sourced facet.
            foreach (self::definitions() as $key => $other) {
                if ($key !== $facet && $other['source'] === 'company' && isset($filters[$key])) {
                    self::constrain($query, $other['expression'], $filters[$key]);
                }
            }

            $query->join('ratings', fn (JoinClause $join) => $join
                ->on('ratings.company_id', '=', 'companies.id')
                ->where('ratings.status', '=', 'approved'));

            foreach (self::ratingFilters($filters, $facet) as $otherExpression => $values) {
                self::constrain($query, $otherExpression, $values);
            }

            $rows = $query->toBase()
                ->selectRaw("{$expression} as facet_value, count(distinct companies.id) as aggregate")
                ->groupBy(DB::raw($expression))
                ->get();
        }

        $counts = [];

        foreach ($rows as $row) {
            $value = $row->facet_value === null ? self::NONE : (string) $row->facet_value;
            $counts[$value] = (int) $row->aggregate;
        }

        return $counts;
    }

    /**
     * Rating-sourced selections keyed by SQL expression, so callers can apply
     * them to either a `whereHas` subquery or a join.
     *
     * @param  array<string, list<string>>  $filters
     * @return array<string, list<string>>
     */
    protected static function ratingFilters(array $filters, ?string $except): array
    {
        $applicable = [];

        foreach (self::definitions() as $facet => $definition) {
            if ($facet === $except || $definition['source'] !== 'rating' || ! isset($filters[$facet])) {
                continue;
            }

            $applicable[$definition['expression']] = $filters[$facet];
        }

        return $applicable;
    }

    /**
     * OR the selected values of a single facet, treating the NONE sentinel as
     * `IS NULL`.
     *
     * @param  list<string>  $values
     */
    protected static function constrain(Builder $query, string $expression, array $values): void
    {
        $concrete = array_values(array_diff($values, [self::NONE]));
        $includesNone = in_array(self::NONE, $values, true);

        $query->where(function (Builder $group) use ($expression, $concrete, $includesNone): void {
            if ($concrete !== []) {
                $group->whereIn(DB::raw($expression), $concrete);
            }

            if ($includesNone) {
                $concrete === []
                    ? $group->whereNull(DB::raw($expression))
                    : $group->orWhereNull(DB::raw($expression));
            }
        });
    }

    /**
     * @param  list<string>|null  $order
     * @param  array<string, int>  $counts
     * @return list<array{value: string, label: string, count: int}>
     */
    protected static function sortOptions(string $facet, ?array $order, array $counts): array
    {
        $options = [];

        foreach ($counts as $value => $count) {
            $options[] = [
                'value' => (string) $value,
                'label' => self::optionLabel($facet, (string) $value),
                'count' => $count,
            ];
        }

        // "غير محدد" always sinks to the bottom; the rest follow the facet's
        // declared order, or popularity when it has none.
        usort($options, function (array $a, array $b) use ($order): int {
            if (($a['value'] === self::NONE) !== ($b['value'] === self::NONE)) {
                return $a['value'] === self::NONE ? 1 : -1;
            }

            if ($order !== null) {
                $rank = fn (string $value): int => ($index = array_search($value, $order, true)) === false
                    ? PHP_INT_MAX
                    : $index;

                return $rank($a['value']) <=> $rank($b['value']);
            }

            return [$b['count'], $a['label']] <=> [$a['count'], $b['label']];
        });

        return $options;
    }
}
