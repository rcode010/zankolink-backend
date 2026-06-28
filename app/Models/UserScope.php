<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class UserScope extends Model
{
    protected $fillable = [
        'role_id',
        'user_id',
        'scope_id',
        'scope_type',
    ];
    public function role(){
        return $this->belongsTo(Role::class);
    }
}
