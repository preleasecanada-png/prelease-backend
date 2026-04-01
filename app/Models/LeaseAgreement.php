<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaseAgreement extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'renter_id',
        'landlord_id',
        'rental_application_id',
        'booking_id',
        'lease_type',
        'start_date',
        'end_date',
        'monthly_rent',
        'total_rent',
        'support_fee',
        'commission_fee',
        'insurance_fee',
        'total_payable',
        'status',
        'renter_signed_at',
        'landlord_signed_at',
        'lease_document_path',
        'terms',
        'special_conditions',
    ];

    protected $casts = [
        'monthly_rent' => 'decimal:2',
        'total_rent' => 'decimal:2',
        'support_fee' => 'decimal:2',
        'commission_fee' => 'decimal:2',
        'insurance_fee' => 'decimal:2',
        'total_payable' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'renter_signed_at' => 'datetime',
        'landlord_signed_at' => 'datetime',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function renter()
    {
        return $this->belongsTo(User::class, 'renter_id');
    }

    public function landlord()
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }

    public function rentalApplication()
    {
        return $this->belongsTo(RentalApplication::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function insurance()
    {
        return $this->hasOne(RentalInsurance::class);
    }
}
