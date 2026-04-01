<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'renter_id',
        'landlord_id',
        'cover_letter',
        'employment_status',
        'monthly_income',
        'current_address',
        'reason_for_moving',
        'number_of_occupants',
        'has_pets',
        'pet_details',
        'desired_move_in',
        'desired_lease_duration',
        'reference_name_1',
        'reference_phone_1',
        'reference_email_1',
        'reference_name_2',
        'reference_phone_2',
        'reference_email_2',
        'status',
        'landlord_notes',
        'rejection_reason',
        'submitted_at',
        'reviewed_at',
    ];

    protected $casts = [
        'monthly_income' => 'decimal:2',
        'has_pets' => 'boolean',
        'desired_move_in' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
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

    public function documents()
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function leaseAgreement()
    {
        return $this->hasOne(LeaseAgreement::class);
    }
}
