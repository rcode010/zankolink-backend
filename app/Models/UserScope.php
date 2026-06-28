<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserScope extends Model
{
    protected $fillable = [
        'role_id',
        'user_id',
        'scope_id',
        'scope_type',
    ];
}
