<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class HighSchoolStudent extends Model
{
    use HasApiTokens, HasFactory;

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $fillable = [
        'code',
        'name',
        'major_type',
        'gender',
        'is_active',
        'status',
        'password',
        'grade_average',
        'grade_10',
        'grade_11',
        'accepted_department_offering_id',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'grade_average' => 'decimal:3',
            'grade_10' => 'decimal:3',
            'grade_11' => 'decimal:3',
        ];
    }
}
