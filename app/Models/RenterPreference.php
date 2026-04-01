<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenterPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'preferred_city',
        'budget_min',
        'budget_max',
        'property_type',
        'min_bedrooms',
        'min_bathrooms',
        'preferred_move_in',
        'preferred_move_out',
        'lease_duration',
        'preferred_amenities',
        'pets_allowed',
        'max_guests',
    ];

    protected $casts = [
        'preferred_amenities' => 'array',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'pets_allowed' => 'boolean',
        'preferred_move_in' => 'date',
        'preferred_move_out' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
