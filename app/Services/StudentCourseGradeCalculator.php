<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseAssessments;
use Illuminate\Support\Facades\DB;

class StudentCourseGradeCalculator
{
    /**
     * Used when marks are submitted for one assessment.
     */
    public function recalculateForAssessment(
        CourseAssessments $assessment,
        array $studentIds
    ): void {
        $this->recalculate(
            courseId: $assessment->course_id,
            academicYearId: $assessment->academic_year_id,
            studentIds: $studentIds,
        );
    }

    /**
     * Used when marks across the whole gradebook are submitted.
     */
    public function recalculateForCourse(
        Course $course,
        int $academicYearId,
        array $studentIds
    ): void {
        $this->recalculate(
            courseId: $course->id,
            academicYearId: $academicYearId,
            studentIds: $studentIds,
        );
    }

    /**
     * Shared calculation logic.
     */
    private function recalculate(
        int $courseId,
        int $academicYearId,
        array $studentIds
    ): void {
        $studentIds = collect($studentIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($studentIds)) {
            return;
        }

        $totals = DB::table('course_student as cs')
            ->leftJoin('course_assessments as ca', function ($join) {
                $join
                    ->on('ca.course_id', '=', 'cs.course_id')
                    ->on(
                        'ca.academic_year_id',
                        '=',
                        'cs.academic_year_id'
                    )
                    ->whereNull('ca.deleted_at');
            })
            ->leftJoin('student_marks as sm', function ($join) {
                $join
                    ->on('sm.course_assessment_id', '=', 'ca.id')
                    ->on('sm.student_id', '=', 'cs.student_id')
                    ->where('sm.status', '=', 'valid')
                    ->whereNotNull('sm.mark');
            })
            ->where('cs.course_id', $courseId)
            ->where('cs.academic_year_id', $academicYearId)
            ->whereIn('cs.student_id', $studentIds)
            ->groupBy('cs.student_id')
            ->selectRaw('
                cs.student_id,
                COALESCE(
                    SUM(
                        (
                            (1.0 * sm.mark)
                            / NULLIF(ca.max_mark, 0)
                        )
                        * COALESCE(ca.weight, 0)
                    ),
                    0
                ) AS total_grade
            ')
            ->pluck('total_grade', 'student_id');

        foreach ($studentIds as $studentId) {
            DB::table('course_student')
                ->where('course_id', $courseId)
                ->where('academic_year_id', $academicYearId)
                ->where('student_id', $studentId)
                ->update([
                    'grade' => round(
                        (float) ($totals[$studentId] ?? 0),
                        2
                    ),
                    'updated_at' => now(),
                ]);
        }
    }
}
