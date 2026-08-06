<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
    protected $fillable = [
        'name',
        'major_type',
        'credit_number',
        'year_level',
    ];

    public function students()
    {
        return $this->belongsToMany(
            HighSchoolStudent::class,
            'subject_student',
            'subject_id',
            'student_id'
        )->withPivot('grade')
            ->withTimestamps();
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(
            Department::class,
            'department_offering_subjects',
            'subject_id',
            'department_id'
        )->withPivot('credit', 'minimum_grade')
            ->withTimestamps();
    }
}
