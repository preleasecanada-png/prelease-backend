<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'renter_id',
        'landlord_id',
        'move_in_date',
        'move_out_date',
        'guests',
        'infront_count',
        'adult_count',
        'child_count',
        'pets_count',
        'price_agreed',
        'status',
        'duration'
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
}