<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentApplication extends Model
{
    protected $fillable = [
        'student_id',
        'academic_year_id',
        'status',
        'draft_choices',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'draft_choices' => 'array',
        ];
    }

    public function student()
    {
        return $this->belongsTo(
            HighSchoolStudent::class,
            'student_id'
        );
    }

    public function academicYear()
    {
        return $this->belongsTo(
            AcademicYear::class
        );
    }
}
