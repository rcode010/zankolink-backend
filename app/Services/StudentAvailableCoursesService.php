<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\User;

class StudentAvailableCoursesService
{
    public function run(User $user)
    {
        $student = Student::query()
            ->where('user_id', $user->id)
            ->firstOrFail();

        $academicYear = AcademicYear::query()
            ->where('is_active', true)
            ->firstOrFail();
        return $student->department
            ->courses()
            ->where('is_active', true)
            ->where('year_level', $student->stage)
            ->where('semester', $academicYear->semester)
            ->with([
                'teachers.user',
                'department',
            ])
            ->orderBy('semester')
            ->orderBy('name')
            ->get();
    }
}
