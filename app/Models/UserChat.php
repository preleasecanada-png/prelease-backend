<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserChat extends Model
{
    use HasFactory;


    protected $table = 'user_chats';

    protected $fillable = ['sender_id', 'received_id', 'message','type'];
}
