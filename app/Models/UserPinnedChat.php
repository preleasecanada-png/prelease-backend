<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPinnedChat extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'pinned_user_id'];
}
