<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseAssessments extends Model
{
    //
    protected $fillable = ['course_id', 'academic_year_id', 'title', 'type', 'max_mark', 'weight', 'due_at', 'teacher_id', 'is_published'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
