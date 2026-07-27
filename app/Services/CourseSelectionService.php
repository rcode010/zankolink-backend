<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CourseSelection;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseSelectionService
{
    public function store(User $user, array $validated): void
    {
        $student = $user->student;

        if (! $student) {
            throw ValidationException::withMessages([
                'student' => ['Student record not found.'],
            ]);
        }

        $academicYear = AcademicYear::query()
            ->where('is_active', true)
            ->firstOrFail();

        $alreadyRequested = CourseSelection::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('semester', $academicYear->semester)
            ->exists();

        if ($alreadyRequested) {
            throw ValidationException::withMessages([
                'course_selection' => [
                    'You have already submitted your course selection.',
                ],
            ]);
        }

        $courses = $this->validateCourses(
            $student,
            $validated['course_ids'],
            $academicYear
        );

        $rows = $courses->map(fn (Course $course) => [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'academic_year_id' => $academicYear->id,
            'semester' => $academicYear->semester,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        DB::transaction(function () use ($rows) {
            CourseSelection::insert($rows);
        });
    }

    private function validateCourses(
        Student $student,
        array $courseIds,
        AcademicYear $academicYear
    ): Collection {
        $courses = Course::query()
            ->whereIn('id', $courseIds)
            ->where('department_id', $student->department_id)
            ->where('year_level', $student->stage)
            ->where('semester', $academicYear->semester)
            ->where('is_active', true)
            ->get();

        if ($courses->count() !== count($courseIds)) {
            throw ValidationException::withMessages([
                'course_ids' => [
                    'One or more selected courses are invalid.',
                ],
            ]);
        }

        return $courses;
    }
}
