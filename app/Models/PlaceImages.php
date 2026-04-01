<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaceImages extends Model
{
    use HasFactory;

    protected $table = 'place_images';

    protected $fillable = ['original', 'extension', 'place_id'];
}
