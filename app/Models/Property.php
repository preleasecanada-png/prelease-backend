<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $table = 'properties';

    protected $fillable = ['title', 'slug', 'description', 'user_id', 'guest', 'describe_your_place', 'country', 'street_address', 'city', 'postal_code', 'state', 'how_many_guests', 'how_many_bedrooms', 'how_many_bathroom', 'bathroom_avaiable_private_and_attached', 'bathroom_avaiable_dedicated', 'bathroom_avaiable_shared', 'who_else_there', 'confirm_reservation', 'set_your_price', 'guest_service_fee' , 'street' , 'address_line_2', 'tour_video_path', 'tour_3d_serialize', 'tour_3d_status', 'tour_3d_model_url', 'tour_3d_processed_at', 'tour_3d_error'];

    protected $casts = [
        'tour_3d_processed_at' => 'datetime',
    ];


    public function propertyGuestImages()
    {
        return $this->belongsToMany(Amenties::class, 'amenities', 'property_id', 'amenity_id');
    }

    public function amenities()
    {
        return $this->belongsToMany(Amenties::class, 'property_guest_amenities', 'property_id', 'amenity_id');
    }
    public function propertyImages()
    {
        return $this->hasMany(PropertyImages::class, 'property_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'property_id', 'id');
    }
    // public function amenities()
    // {
    //     return $this->belongsToMany(Amenties::class, 'property_guest_amenities', 'property_id', 'amenity_id');
    // }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
