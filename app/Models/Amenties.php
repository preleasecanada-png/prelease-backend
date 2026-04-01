<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Amenties extends Model
{
    use HasFactory;

    protected $table = 'amenities';

    protected $fillable = ['name', 'image'];

    public function properties()
    {
        return $this->belongsToMany(Property::class, 'property_guest_amenities', 'amenity_id', 'property_id');
    }
}
