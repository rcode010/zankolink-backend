<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\CourseSelection;
use App\Models\Student;
use Illuminate\Support\Collection;

class UniqueCourseRequestService
{
    /**
     * Create a new class instance.
     */
    public function requestedCourses(Student $student): ?Collection
    {
        $academicYear = AcademicYear::query()
            ->where('is_active', true)
            ->firstOrFail();
        $courses = CourseSelection::query()
            ->with('course')
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('semester', $academicYear->semester)
            ->get()
            ->pluck('course')
            ->filter()
            ->values();

        return $courses->isEmpty() ? null : $courses;
    }
}
