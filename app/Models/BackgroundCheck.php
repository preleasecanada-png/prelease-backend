<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackgroundCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_application_id',
        'renter_id',
        'landlord_id',
        'check_type',
        'status',
        'renter_consent',
        'consent_given_at',
        'credit_score',
        'credit_rating',
        'credit_summary',
        'criminal_result',
        'criminal_summary',
        'notes',
        'fee_amount',
        'fee_paid_by',
        'completed_at',
    ];

    protected $casts = [
        'renter_consent' => 'boolean',
        'consent_given_at' => 'datetime',
        'completed_at' => 'datetime',
        'fee_amount' => 'decimal:2',
    ];

    public function rentalApplication()
    {
        return $this->belongsTo(RentalApplication::class);
    }

    public function renter()
    {
        return $this->belongsTo(User::class, 'renter_id');
    }

    public function landlord()
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }
}
