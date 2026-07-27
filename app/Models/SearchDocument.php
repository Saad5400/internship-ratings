<?php

namespace App\Models;

use App\Enums\SearchField;
use App\Support\Search\CompanySearch;
use App\Support\Search\SearchIndexer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One indexed field of one searchable record.
 *
 * Written only by {@see SearchIndexer} and read only by
 * {@see CompanySearch} — engine internals, not a domain
 * model to query from pages.
 */
class SearchDocument extends Model
{
    protected $fillable = [
        'searchable_type',
        'searchable_id',
        'field',
        'content',
        'content_hash',
        'embedding',
        'embedding_model',
        'dimensions',
        'indexed_at',
    ];

    protected function casts(): array
    {
        return [
            'field' => SearchField::class,
            'dimensions' => 'integer',
            'indexed_at' => 'datetime',
        ];
    }

    public function searchable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('searchable_type', $type);
    }

    /** Rows still waiting on a vector — what a resumed re-index picks up. */
    public function scopeUnembedded(Builder $query): Builder
    {
        return $query->whereNull('embedding');
    }
}
