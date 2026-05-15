<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiChatHistory extends Model
{
    protected $table = 'ai_chat_history';

    protected $fillable = ['user_id', 'role', 'content'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
