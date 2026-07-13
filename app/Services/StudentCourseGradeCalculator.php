<?php

namespace App\Services;

use App\Models\CourseAssessments;
use Illuminate\Support\Facades\DB;

class StudentCourseGradeCalculator
{
    public function execute(CourseAssessments $assessment, array $studentIds): void
    {
        if (empty($studentIds)) {
            return;
        }

        $studentIds = collect($studentIds)
            ->unique()
            ->values()
            ->all();

        $totals = DB::table('student_marks')
            ->join(
                'course_assessments',
                'student_marks.course_assessment_id',
                '=',
                'course_assessments.id'
            )
            ->whereIn('student_marks.student_id', $studentIds)
            ->where('course_assessments.course_id', $assessment->course_id)
            ->where('course_assessments.academic_year_id', $assessment->academic_year_id)
            ->where('student_marks.status', 'valid')
            ->whereNotNull('student_marks.mark')
            ->whereNull('course_assessments.deleted_at')
            ->groupBy('student_marks.student_id')
            ->selectRaw('
                student_marks.student_id,
                COALESCE(
                    SUM(
                        (
                            (1.0 * student_marks.mark)
                            / NULLIF(course_assessments.max_mark, 0)
                        )
                        * COALESCE(course_assessments.weight, 0)
                    ),
                    0
                ) as total_grade
            ')
            ->pluck('total_grade', 'student_marks.student_id');

        foreach ($studentIds as $studentId) {
            DB::table('course_student')
                ->where('course_id', $assessment->course_id)
                ->where('student_id', $studentId)
                ->where('academic_year_id', $assessment->academic_year_id)
                ->update([
                    'grade' => round((float) ($totals[$studentId] ?? 0), 2),
                    'updated_at' => now(),
                ]);
        }
    }
}
