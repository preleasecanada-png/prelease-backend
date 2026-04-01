<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyGuestAmenities extends Model
{
    use HasFactory;

    protected $table = 'property_guest_amenities';

    protected $fillable = ['property_id', 'amenity_id'];
}
