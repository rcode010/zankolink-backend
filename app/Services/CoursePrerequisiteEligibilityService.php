<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class CoursePrerequisiteEligibilityService
{
    public function check(Student $student, Course $course): array
    {
        $prerequisiteIds = $course->prerequisites()
            ->where('course_id', $course->id)
            ->pluck('prerequisite_course_id')
            ->toArray();
        if (empty($prerequisiteIds)) {
            return [
                'eligible' => true,
                'missing_prerequisites' => [],
            ];
        }

        $passedPrerequisiteIds = DB::table('course_student')
            ->where('student_id', $student->id)
            ->whereIn('course_id', $prerequisiteIds)
            ->where('status', 'passed')
            ->where('grade', '>=', 50)
            ->pluck('course_id')
            ->toArray();
        $missingPrerequisites = array_values(array_diff(
            $prerequisiteIds,
            $passedPrerequisiteIds
        ));

        return [
            'eligible' => empty($missingPrerequisites),
            'missing_prerequisites' => $missingPrerequisites,
        ];
    }
}
