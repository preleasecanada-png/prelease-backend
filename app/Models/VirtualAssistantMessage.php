<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirtualAssistantMessage extends Model
{
    use HasFactory;

    protected $table = 'virtual_assistant_messages';

    protected $fillable = [
        'conversation_id',
        'sender',
        'message',
        'attachments',
        'is_ai_generated',
        'model_used',
        'tokens_used',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_ai_generated' => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(VirtualAssistantConversation::class, 'conversation_id');
    }

    public function scopeFromUser($query)
    {
        return $query->where('sender', 'user');
    }

    public function scopeFromAssistant($query)
    {
        return $query->where('sender', 'assistant');
    }
}
