<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_reference',
        'lease_agreement_id',
        'renter_id',
        'landlord_id',
        'property_id',
        'rent_amount',
        'support_fee',
        'commission_fee',
        'insurance_fee',
        'total_amount',
        'payment_type',
        'payment_method',
        'transaction_id',
        'status',
        'landlord_payout_status',
        'landlord_payout_amount',
        'landlord_paid_at',
        'insurance_payout_status',
        'insurance_payout_amount',
        'notes',
        'paid_at',
    ];

    protected $casts = [
        'rent_amount' => 'decimal:2',
        'support_fee' => 'decimal:2',
        'commission_fee' => 'decimal:2',
        'insurance_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'landlord_payout_amount' => 'decimal:2',
        'insurance_payout_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'landlord_paid_at' => 'datetime',
    ];

    public function leaseAgreement()
    {
        return $this->belongsTo(LeaseAgreement::class);
    }

    public function renter()
    {
        return $this->belongsTo(User::class, 'renter_id');
    }

    public function landlord()
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
