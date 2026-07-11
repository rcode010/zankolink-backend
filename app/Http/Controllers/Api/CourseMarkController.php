<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseAssessments;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

/**
 * @group Course Marks
 *
 * APIs for students to view their marks in a specific course.
 */
class CourseMarkController extends Controller
{
    use ApiResponses;

    /**
     * View my course marks
     *
     * Retrieve the authenticated student's marks for all assessments in a specific course.
     *
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Course marks retrieved successfully",
     *   "data": {
     *     "course": {
     *       "id": 1,
     *       "name": "Database Systems",
     *       "code": "DB101"
     *     },
     *     "assessments": [
     *       {
     *         "assessment_id": 1,
     *         "title": "Quiz 1",
     *         "type": "quiz",
     *         "max_mark": "10.00",
     *         "weight": "5.00",
     *         "mark_id": 1,
     *         "mark": "8.50",
     *         "status": "valid",
     *         "feedback": "Good work",
     *         "graded_at": "2026-07-08 09:30:00"
     *       },
     *       {
     *         "assessment_id": 2,
     *         "title": "Final Exam",
     *         "type": "final",
     *         "max_mark": "60.00",
     *         "weight": "50.00",
     *         "mark_id": null,
     *         "mark": null,
     *         "status": null,
     *         "feedback": null,
     *         "graded_at": null
     *       }
     *     ]
     *   }
     * }
     */
    public function myMarks(Request $request, Course $course)
    {
        $student = $request->user()->student;
        $assessments = CourseAssessments::query()
            ->where('course_id', $course->id)
            ->with([
                'marks' => function ($query) use ($student) {
                    $query->where('student_id', $student->id);
                },
            ])
            ->orderBy('created_at')
            ->get();

        $data = $assessments->map(function ($assessment) {
            $mark = $assessment->marks->first();

            return [
                'assessment_id' => $assessment->id,
                'title' => $assessment->title,
                'type' => $assessment->type,
                'max_mark' => $assessment->max_mark,
                'weight' => $assessment->weight,

                'mark_id' => $mark?->id,
                'mark' => $mark?->mark,
                'status' => $mark?->status,
                'feedback' => $mark?->feedback,
                'graded_at' => $mark?->graded_at,
            ];
        });

        return $this->ok('Course marks retrieved successfully', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'code' => $course->code ?? null,
            ],
            'assessments' => $data,
        ]);
    }
}
