<?php

namespace App\Models;

use App\Enums\CompanyType;
use App\Support\Arabic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

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

    public function getAverageRatingAttribute(): ?float
    {
        $avg = $this->ratings()->avg('overall_rating');

        return $avg ? round($avg, 1) : null;
    }

    public function getRatingsCountAttribute(): int
    {
        return $this->ratings()->count();
    }
}
