<?php

namespace App\Models;

use App\Enums\Modality;
use App\Enums\Recommendation;
use App\Enums\ReviewerDegree;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    public const RECOMMENDATION_PROMOTER_THRESHOLD = 4.0;

    public const RECOMMENDATION_PASSIVE_THRESHOLD = 3.0;

    protected $fillable = [
        'company_id',
        'role_title',
        'department',
        'city',
        'duration_months',
        'modality',
        'stipend_sar',
        'had_supervisor',
        'mixed_env',
        'job_offer',
        'rating_mentorship',
        'rating_learning',
        'rating_real_work',
        'rating_team_environment',
        'rating_organization',
        'overall_rating',
        'recommendation',
        'review_text',
        'pros',
        'cons',
        'reviewer_name',
        'reviewer_university',
        'reviewer_college',
        'reviewer_major',
        'reviewer_degree',
        'application_method',
        'willing_to_help',
        'contact_method',
    ];

    protected function casts(): array
    {
        return [
            'modality' => Modality::class,
            'recommendation' => Recommendation::class,
            'reviewer_degree' => ReviewerDegree::class,
            'duration_months' => 'integer',
            'stipend_sar' => 'integer',
            'had_supervisor' => 'boolean',
            'mixed_env' => 'boolean',
            'job_offer' => 'boolean',
            'willing_to_help' => 'boolean',
            'rating_mentorship' => 'integer',
            'rating_learning' => 'integer',
            'rating_real_work' => 'integer',
            'rating_team_environment' => 'integer',
            'rating_organization' => 'integer',
            'overall_rating' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Rating $rating): void {
            $overallRating = $rating->calculateOverallRating();

            if ($overallRating !== null) {
                $rating->overall_rating = $overallRating;
            }

            if (blank($rating->recommendation) && $rating->overall_rating !== null) {
                $rating->recommendation = static::recommendationFromOverall($rating->overall_rating);
            }
        });
    }

    /**
     * @return array<string, float>
     */
    public static function metricWeights(): array
    {
        return [
            'rating_learning' => 0.30,
            'rating_mentorship' => 0.25,
            'rating_real_work' => 0.20,
            'rating_team_environment' => 0.15,
            'rating_organization' => 0.10,
        ];
    }

    public static function recommendationFromOverall(?float $overallRating): ?Recommendation
    {
        if ($overallRating === null) {
            return null;
        }

        return match (true) {
            $overallRating >= static::RECOMMENDATION_PROMOTER_THRESHOLD => Recommendation::Yes,
            $overallRating >= static::RECOMMENDATION_PASSIVE_THRESHOLD => Recommendation::Maybe,
            default => Recommendation::No,
        };
    }

    public function calculateOverallRating(): ?float
    {
        $weightedScore = 0.0;

        foreach (static::metricWeights() as $field => $weight) {
            $value = $this->{$field};

            if ($value === null) {
                return null;
            }

            $weightedScore += $value * $weight;
        }

        return round($weightedScore, 1);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
