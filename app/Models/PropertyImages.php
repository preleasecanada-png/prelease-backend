<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PropertyImages extends Model
{
    use HasFactory;

    protected $table = 'property_images';

    protected $fillable = ['property_id', 'extension', 'original'];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): string
    {
        if (!$this->original) {
            return '';
        }
        return Storage::disk('s3')->url($this->original);
    }
}
