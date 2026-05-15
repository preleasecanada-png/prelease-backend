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
        $path = is_array($this->original) ? ($this->original['original'] ?? '') : $this->original;
        if (!$path) {
            return '';
        }
        // Return direct S3 URL (bucket must be public for this to work in React Native)
        return Storage::disk('s3')->url($path);
    }
}
