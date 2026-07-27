<?php

namespace App\Models;

use App\Support\PopularSearches;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * An aggregate counter for one distinct search term.
 *
 * Written and read only through {@see PopularSearches} — a counter row, not a
 * domain model to query from pages. It holds no user identifiers of any kind;
 * see the migration for the full privacy contract.
 */
class PopularSearch extends Model
{
    /**
     * No `created_at`/`updated_at`: a creation timestamp on a term first
     * searched by one person is exactly the correlation this table refuses to
     * keep. `last_searched_at` (hour-granular) is the only time stored.
     */
    public $timestamps = false;

    protected $fillable = [
        'term',
        'display_term',
        'search_count',
        'last_searched_at',
    ];

    protected function casts(): array
    {
        return [
            'search_count' => 'integer',
            'last_searched_at' => 'datetime',
        ];
    }

    /** Terms popular enough, and recent enough, to be worth showing. */
    public function scopeSurfaceable(Builder $query): Builder
    {
        return $query
            ->where('search_count', '>=', PopularSearches::MIN_COUNT)
            ->where('last_searched_at', '>=', now()->subDays(PopularSearches::RECENT_DAYS));
    }
}
