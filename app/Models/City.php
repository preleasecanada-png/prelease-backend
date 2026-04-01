<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $table = 'cities';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'picture',
    ];

    public function places()
    {
        return $this->hasMany(Place::class, 'city_id', 'id');
    }
}
