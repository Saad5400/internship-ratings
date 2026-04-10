<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    protected $fillable = [
        'company_id',
        'role_title',
        'department',
        'city',
        'duration_months',
        'sector',
        'modality',
        'stipend_sar',
        'had_supervisor',
        'mixed_env',
        'job_offer',
        'rating_mentorship',
        'rating_learning',
        'rating_culture',
        'rating_compensation',
        'overall_rating',
        'recommendation',
        'review_text',
        'pros',
        'cons',
        'reviewer_name',
        'reviewer_major',
    ];

    protected function casts(): array
    {
        return [
            'duration_months' => 'integer',
            'stipend_sar' => 'integer',
            'had_supervisor' => 'boolean',
            'mixed_env' => 'boolean',
            'job_offer' => 'boolean',
            'rating_mentorship' => 'integer',
            'rating_learning' => 'integer',
            'rating_culture' => 'integer',
            'rating_compensation' => 'integer',
            'overall_rating' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
