<?php

namespace App\Models;

use App\Enums\CompanyType;
use App\Jobs\IndexSearchDocuments;
use App\Support\Arabic;
use App\Support\CompanyBranding;
use App\Support\CompanyFacets;
use App\Support\Search\CompanySearch;
use App\Support\Search\SearchIndexer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    protected $fillable = [
        'name',
        'type',
        'website',
        'description',
        'status',
        'is_imported',
    ];

    protected function casts(): array
    {
        return [
            'type' => CompanyType::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Company $company) {
            $company->name_normalized = Arabic::normalize($company->name);
        });

        // Re-indexing is a paid embedding call, so it never runs inline on a
        // write — the job is unique per company, which also collapses the burst
        // a bulk moderation pass would otherwise create.
        static::saved(function (Company $company) {
            IndexSearchDocuments::dispatch($company->getKey());
        });

        static::deleted(function (Company $company) {
            app(SearchIndexer::class)->forget($company);
        });
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function approvedRatings(): HasMany
    {
        return $this->hasMany(Rating::class)->where('status', 'approved');
    }

    /**
     * The single most recent approved rating for this company, used to
     * surface a fresh quote on the company card without an N+1 query.
     */
    public function latestApprovedRating(): HasOne
    {
        return $this->ratings()->one()->ofMany(
            ['created_at' => 'max'],
            fn (Builder $query) => $query->where('status', 'approved')
        );
    }

    /**
     * Columns are table-qualified so these scopes stay unambiguous when the
     * query joins `ratings` (which carries its own `status`) — see the faceted
     * counts in {@see CompanyFacets}.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('companies.status', 'approved');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('companies.status', 'pending');
    }

    /**
     * Plain name-only matching. Kept for pickers that want a literal,
     * index-free lookup (the rating wizard's company field); public browsing
     * goes through {@see scopeMatchingSearch} instead.
     */
    public function scopeSearchByName(Builder $query, ?string $term): Builder
    {
        $normalized = Arabic::normalize($term);

        if ($normalized === '') {
            return $query;
        }

        $words = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($words as $word) {
            $query->where('name_normalized', 'like', '%'.$word.'%');
        }

        return $query;
    }

    /**
     * Restrict to companies the hybrid search matched.
     *
     * The single entry point for "what does this query return" — results and
     * facet counts both go through it, so the count on a filter chip always
     * describes the list the user is actually looking at.
     */
    public function scopeMatchingSearch(Builder $query, ?string $term): Builder
    {
        if (Arabic::normalize($term) === '') {
            return $query;
        }

        $search = app(CompanySearch::class);

        // Deploys migrate before they index. Ranking against an empty index is
        // technically correct and catastrophic — every search would return
        // nothing — so fall back to literal name matching until it is built.
        if (! $search->hasIndex()) {
            return $query->searchByName($term);
        }

        $ids = $search->ids($term);

        return $ids === []
            ? $query->whereRaw('1 = 0')
            : $query->whereIn('companies.id', $ids);
    }

    /**
     * Order by how well each company matched, best first.
     *
     * The rank is materialised as a CASE because the ordering has to happen in
     * SQL: facets narrow the set after search does, and the page paginates the
     * result.
     */
    public function scopeOrderByRelevance(Builder $query, ?string $term): Builder
    {
        $ids = Arabic::normalize($term) === '' ? [] : app(CompanySearch::class)->ids($term);

        if ($ids === []) {
            return $query;
        }

        $bindings = [];
        $cases = '';

        foreach (array_values($ids) as $rank => $id) {
            $cases .= ' when ? then ?';
            $bindings[] = $id;
            $bindings[] = $rank;
        }

        $bindings[] = count($ids);

        return $query->orderByRaw("case companies.id{$cases} else ? end asc", $bindings);
    }

    /**
     * The approved company this one most likely duplicates, if any — matched
     * on the normalized name (exact or containment either way). Used by the
     * moderation flows to suggest reassigning ratings instead of approving a
     * duplicate entry.
     */
    public function findSimilarApproved(): ?Company
    {
        $normalized = $this->name_normalized ?? Arabic::normalize($this->name);

        if (blank($normalized)) {
            return null;
        }

        return static::approved()
            ->whereKeyNot($this->getKey())
            ->where(function (Builder $query) use ($normalized) {
                $query->where('name_normalized', $normalized)
                    ->orWhere('name_normalized', 'like', '%'.$normalized.'%')
                    ->orWhereRaw("? like '%' || name_normalized || '%'", [$normalized]);
            })
            ->withCount('ratings as ratings_total_count')
            ->orderByRaw('length(name_normalized) asc')
            ->first();
    }

    /**
     * Eloquent always prefers an accessor over a same-named eager-loaded
     * column, so callers that already paid for `withAvg`/`withCount` (see
     * companies/index.blade.php's `companyResults()`) must not be made to
     * pay again here — the raw attribute is used when it's already present,
     * and only falls back to a live query otherwise.
     */
    public function getAverageRatingAttribute(): ?float
    {
        if (array_key_exists('average_rating', $this->attributes)) {
            $avg = $this->attributes['average_rating'];

            return $avg === null ? null : round((float) $avg, 1);
        }

        $avg = $this->approvedRatings()->avg('overall_rating');

        return $avg ? round($avg, 1) : null;
    }

    public function getRatingsCountAttribute(): int
    {
        if (array_key_exists('ratings_count', $this->attributes)) {
            return (int) $this->attributes['ratings_count'];
        }

        return $this->approvedRatings()->count();
    }

    /**
     * Public favicon CDN URL for the company's website, or null when no
     * website is set. No asset is ever stored server-side.
     *
     * There is no logo column: the mark is derived from the website, and
     * {@see CompanyBranding} owns that derivation so the backfill command
     * previews exactly what the avatar will render.
     */
    public function getFaviconUrlAttribute(): ?string
    {
        return CompanyBranding::logoUrl($this->website);
    }

    /**
     * First character of the company name, used as a fallback avatar glyph.
     * Uses mb_substr so it works correctly with Arabic names.
     */
    public function getInitialAttribute(): string
    {
        return mb_substr(trim($this->name), 0, 1);
    }
}
