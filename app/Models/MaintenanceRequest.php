<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'landlord_id',
        'property_id',
        'lease_id',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'photos',
        'landlord_response',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'photos' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function landlord()
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function lease()
    {
        return $this->belongsTo(LeaseAgreement::class, 'lease_id');
    }
}
