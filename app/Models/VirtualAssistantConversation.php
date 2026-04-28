<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VirtualAssistantConversation extends Model
{
    use HasFactory;

    protected $table = 'virtual_assistant_conversations';

    protected $fillable = [
        'user_id',
        'channel',
        'phone_number',
        'status',
        'subject',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(VirtualAssistantMessage::class, 'conversation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }
}
