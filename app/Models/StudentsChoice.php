<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentsChoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'department_offering_id',
        'score',
        'is_local',
        'preference_order',
        'status',
        'academic_year_id',
    ];

    public function department_offering()
    {
        return $this->belongsTo(
            DepartmentOffering::class,
            'department_offering_id'
        );
    }
}
