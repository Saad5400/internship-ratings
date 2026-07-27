<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatingVote extends Model
{
    protected $fillable = [
        'rating_id',
        'voter_hash',
    ];

    public function votes(): BelongsTo
    {
        return $this->belongsTo(Rating::class, 'rating_id');
    }
}
