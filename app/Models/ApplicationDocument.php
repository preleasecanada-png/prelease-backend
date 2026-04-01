<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_application_id',
        'user_id',
        'document_type',
        'file_name',
        'file_path',
        'file_extension',
        'file_size',
        'verification_status',
        'admin_notes',
    ];

    public function rentalApplication()
    {
        return $this->belongsTo(RentalApplication::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
