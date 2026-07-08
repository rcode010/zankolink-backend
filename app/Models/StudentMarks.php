<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentMarks extends Model
{
    protected $fillable = ['student_marks', 'course_assessment_id', 'student_id', 'mark', 'feedback', 'graded_by', 'graded_at'];

    public function courseAssessment()
    {
        return $this->belongsTo(CourseAssessments::class, 'course_assessment_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function gradedBy()
    {
        return $this->belongsTo(Teacher::class, 'graded_by');
    }
}
