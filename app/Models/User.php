<?php

namespace App\Models;

use App\Enums\AllowedRole;
use App\Notifications\QueuedResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
        'two_factor_code',
        'two_factor_expires_at',
        'is_two_factor_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [

            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function userScopes(array $attributes = [])
    {
        return $this->hasMany(UserScope::class);
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function signatures()
    {
        return $this->hasMany(LetterSignature::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function isProtected()
    {
        return $this->role && AllowedRole::tryFrom($this->role->name) == null;
    }
    public function sendPasswordResetNotification($token){
        $this->notify(new QueuedResetPasswordNotification($token));
    }
}
