<?php

namespace App\Services;

use App\Models\CourseSelection;
use App\Models\Student;

class UniqueCourseRequestService
{
    /**
     * Create a new class instance.
     */
    public function requestedCourses(Student $student, int $academicYearId): ?\Illuminate\Support\Collection
    {
        $courses = CourseSelection::query()
            ->with('course')
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYearId)
            ->get()
            ->pluck('course');

        return $courses->isEmpty() ? null : $courses;
    }
}
