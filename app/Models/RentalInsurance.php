<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalInsurance extends Model
{
    use HasFactory;

    protected $table = 'rental_insurance';

    protected $fillable = [
        'lease_agreement_id',
        'renter_id',
        'policy_number',
        'provider',
        'premium_amount',
        'coverage_start',
        'coverage_end',
        'status',
        'coverage_details',
    ];

    protected $casts = [
        'premium_amount' => 'decimal:2',
        'coverage_start' => 'date',
        'coverage_end' => 'date',
    ];

    public function leaseAgreement()
    {
        return $this->belongsTo(LeaseAgreement::class);
    }

    public function renter()
    {
        return $this->belongsTo(User::class, 'renter_id');
    }
}
