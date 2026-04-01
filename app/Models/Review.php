<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'reviewer_id',
        'reviewee_id',
        'lease_agreement_id',
        'review_type',
        'rating',
        'comment',
        'cleanliness_rating',
        'communication_rating',
        'value_rating',
        'location_rating',
        'status',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewee()
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    public function leaseAgreement()
    {
        return $this->belongsTo(LeaseAgreement::class);
    }
}
