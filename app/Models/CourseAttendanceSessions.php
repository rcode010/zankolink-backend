<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseAttendanceSessions extends Model
{
    protected $fillable = ['course_id', 'teacher_id', 'session_date', 'start_at', 'end_at', 'title', 'academic_year_id'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function studentAttendance(): HasMany
    {
        return $this->hasMany(StudentAttendance::class, 'attendance_session_id');
    }
}
