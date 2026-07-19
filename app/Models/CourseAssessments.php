<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseAssessments extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'academic_year_id', 'title', 'max_mark', 'weight', 'due_at', 'teacher_id'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function marks()
    {
        return $this->hasMany(StudentMarks::class, 'course_assessment_id');
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
