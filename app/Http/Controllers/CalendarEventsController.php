<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\SectionSubmission;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CalendarEventsController extends Controller
{
    use ApiResponses;

    public function myAssignments(Request $request)
    {
        $validated = $request->validate([
            'start_date' => [
                'required',
                'date',
            ],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],
            'course_id' => [
                'sometimes',
                'integer',
                'exists:courses,id',
            ],
        ]);

        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Student profile not found.', 404);
        }

        $academicYearId = AcademicYear::query()
            ->where('is_active', true)
            ->value('id');

        if (! $academicYearId) {
            return $this->error('No active academic year found.', 422);
        }

        $startDate = Carbon::parse($validated['start_date'])->startOfDay();
        $endDate = Carbon::parse($validated['end_date'])->endOfDay();

        $assignments = SectionSubmission::query()
            ->select('section_submissions.*')
            ->join(
                'course_assessments',
                'course_assessments.id',
                '=',
                'section_submissions.course_assessment_id'
            )
            ->whereNull('course_assessments.deleted_at')
            ->where(
                'course_assessments.academic_year_id',
                $academicYearId
            )
            ->whereBetween(
                'course_assessments.due_at',
                [$startDate, $endDate]
            )
            ->when(
                isset($validated['course_id']),
                fn ($query) => $query->where(
                    'course_assessments.course_id',
                    $validated['course_id']
                )
            )
            ->whereHas(
                'courseAssessment.course.students',
                function ($query) use ($student, $academicYearId) {
                    $query
                        ->where('students.id', $student->id)
                        ->where(
                            'course_student.academic_year_id',
                            $academicYearId
                        );
                }
            )
            ->with([
                'courseAssessment:id,course_id,title,due_at,max_mark,weight',
                'section.course:id,name,code',
                'studentSubmissions' => fn ($query) => $query
                    ->where('student_id', $student->id),
            ])
            ->orderBy('course_assessments.due_at')
            ->get();

        $data = $assignments->map(function ($assignment) {
            $assessment = $assignment->courseAssessment;
            $course = $assignment->section->course;
            $submission = $assignment->studentSubmissions->first();

            return [
                'id' => $assignment->id,
                'assessment_id' => $assessment->id,
                'title' => $assessment->title,
                'description' => $assignment->description,
                'due_at' => $assessment->due_at,

                'course' => [
                    'id' => $course->id,
                    'name' => $course->name,
                    'code' => $course->code,
                ],

                'is_submitted' => $submission !== null,

                'is_overdue' => ! $submission
                    && $assessment->due_at->isPast(),

                'submission' => $submission
                    ? [
                        'id' => $submission->id,
                        'submitted_at' => $submission->created_at,
                    ]
                    : null,
            ];
        })->values();

        return $this->ok(
            'Assignments retrieved successfully.',
            $data->toArray()
        );
    }
}
