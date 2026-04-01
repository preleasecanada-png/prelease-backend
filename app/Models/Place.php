<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    use HasFactory;

    protected $table = 'places';

    protected $fillable = [
        'name',
        'uuid',
        'slug',
        'picture',
        'description',
        'address',
        'longitude',
        'latitude',
        'adulats_min',
        'adulats_max',
        'children_min',
        'children_max',
        'infant_min',
        'infant_max',
        'pets_min',
        'pets_max',
        'check_in_date',
        'check_out_date',
        'price',
        'price_type',
        'amenities_id',
        'city_id',
        'property_id',
        'zip_code',
        'created_by',
        'currency'
    ];

    public function placeImages()
    {
        return $this->hasMany(PlaceImages::class, 'place_id', 'id');
    }
    public function property()
    {
        return $this->hasOne(Property::class, 'id', 'property_id');
    }
    public function cities()
    {
        return $this->hasMany(City::class, 'city_id', 'id');
    }
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }
}
