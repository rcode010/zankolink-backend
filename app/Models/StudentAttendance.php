<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAttendance extends Model
{
    protected $fillable = ['attendance_session_id', 'student_id', 'status', 'note'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(CourseAttendanceSessions::class, 'attendance_session_id');
    }
}
