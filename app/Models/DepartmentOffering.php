<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentOffering extends Model
{
    use HasFactory;

    protected $fillable = ['department_id', 'academic_year_id', 'track_type', 'governorate', 'major_type', 'city', 'minimum_grade_zankoline', 'minimum_grade_parallel'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function students()
    {
        return $this->belongsToMany(
            HighSchoolStudent::class,
            'student_choice',
            'department_offering_id',
            'student_id'
        )
            ->withPivot('priority', 'status')
            ->withTimestamps();
    }
}
