<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentMarkRequest;
use App\Http\Requests\UpdateStudentMarkRequest;
use App\Models\CourseAssessments;
use App\Models\StudentMarks;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Student Marks
 *
 * APIs for submitting, updating, and viewing student marks for course assessments.
 */
class StudentMarksController extends Controller
{
    use ApiResponses;

    /**
     * List assessment marks
     *
     * Retrieve all submitted marks for a specific course assessment.
     *
     * @authenticated
     *
     * @urlParam assessment integer required The ID of the course assessment. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Retrieved all marks of the assessment",
     *   "data": [
     *     {
     *       "id": 1,
     *       "course_assessment_id": 1,
     *       "student_id": 3,
     *       "mark": "8.50",
     *       "feedback": "Good work",
     *       "graded_by": 2,
     *       "graded_at": "2026-07-08 09:30:00",
     *       "status": "valid",
     *       "created_at": "2026-07-08T09:30:00.000000Z",
     *       "updated_at": "2026-07-08T09:30:00.000000Z"
     *     }
     *   ]
     * }
     */
    public function AllAssessmentMarks(Request $request, CourseAssessments $assessment)
    {
        $this->authorize('viewAny', [StudentMarks::class, $assessment]);

        $marks = QueryBuilder::for(StudentMarks::class)
            ->where('course_assessment_id', $assessment->id)
            ->get();

        return $this->ok('Retrieved all marks of the assessment', $marks->toArray());
    }

    /**
     * Submit assessment marks
     *
     * Submit marks for multiple students in a specific assessment. If a mark already exists for the same student and assessment, it will be updated instead of duplicated.
     *
     * @authenticated
     *
     * @urlParam assessment integer required The ID of the course assessment. Example: 1
     *
     * @bodyParam marks array required List of student marks to submit. Example: [{"student_id":3,"mark":8.5,"feedback":"Good work","status":"valid"}]
     * @bodyParam marks.*.student_id integer required The ID of the student. The student must exist and must be enrolled in the assessment course. Example: 3
     * @bodyParam marks.*.mark number required The student's mark. Must be between 0 and the assessment max mark. Example: 8.5
     * @bodyParam marks.*.feedback string nullable Feedback for the student. Maximum 1000 characters. Example: Good work
     * @bodyParam marks.*.status string nullable Mark status. Must be one of: valid, voided, excused, absent, under_review. Example: valid
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Marks submitted successfully.",
     *   "data": [
     *     {
     *       "course_assessment_id": 1,
     *       "student_id": 3,
     *       "mark": 8.5,
     *       "feedback": "Good work",
     *       "graded_by": 2,
     *       "status": "valid",
     *       "graded_at": "2026-07-08 09:30:00",
     *       "created_at": "2026-07-08 09:30:00",
     *       "updated_at": "2026-07-08 09:30:00"
     *     }
     *   ]
     * }
     * @response 422 {
     *   "message": "Some students are not enrolled in this course.",
     *   "errors": {
     *     "marks": ["Some students are not enrolled in this course."]
     *   }
     * }
     */
    public function store(StoreStudentMarkRequest $request, CourseAssessments $assessment)
    {
        $this->authorize('create', [StudentMarks::class, $assessment]);

        $teacher = $request->user()->teacher;
        $marks = collect($request->validated()['marks'])
            ->map(fn ($mark) => [
                'course_assessment_id' => $assessment->id,
                'student_id' => $mark['student_id'],
                'mark' => $mark['mark'],
                'feedback' => $mark['feedback'] ?? null,
                'graded_by' => $teacher->id,
                'status' => $mark['status'] ?? 'valid',
                'graded_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->toArray();

        DB::table('student_marks')->upsert(
            $marks,
            ['course_assessment_id', 'student_id'], // unique columns to match on
            ['mark', 'feedback', 'updated_at'] // columns to update if row exists
        );

        return $this->ok('Marks submitted successfully.', $marks);
    }

    /**
     * Show student mark
     *
     * Retrieve a specific student mark with assessment, student, teacher, and grader details.
     *
     * @authenticated
     *
     * @urlParam mark integer required The ID of the student mark. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Retrieved marks",
     *   "data": {
     *     "id": 1,
     *     "course_assessment_id": 1,
     *     "student_id": 5,
     *     "mark": 30,
     *     "feedback": "not bad",
     *     "graded_by": {
     *       "id": 1,
     *       "user_id": 5,
     *       "user": {
     *         "id": 5,
     *         "name": "Joshuah Yost",
     *         "email": "clovis.fay@example.com"
     *       }
     *     },
     *     "graded_at": "2026-07-08 09:51:18",
     *     "status": "valid",
     *     "created_at": "2026-07-08T06:50:56.000000Z",
     *     "updated_at": null,
     *     "course_assessment": {
     *       "id": 1,
     *       "course_id": 1,
     *       "teacher_id": 1,
     *       "title": "Quiz",
     *       "type": "midterm",
     *       "max_mark": 30,
     *       "teacher": {
     *         "id": 1,
     *         "user_id": 5,
     *         "user": {
     *           "id": 5,
     *           "name": "Joshuah Yost",
     *           "email": "clovis.fay@example.com"
     *         }
     *       }
     *     },
     *     "student": {
     *       "id": 5,
     *       "user_id": 14,
     *       "user": {
     *         "id": 14,
     *         "name": "Dashawn Pfeffer",
     *         "email": "harley.haag@example.org"
     *       }
     *     }
     *   }
     * }
     */
    public function show(Request $request, StudentMarks $mark)
    {
        $mark->load([
            'courseAssessment:id,course_id,teacher_id,title,type,max_mark',
            'courseAssessment.teacher:id,user_id',
            'courseAssessment.teacher.user:id,name,email',

            'student:id,user_id',
            'student.user:id,name,email',

            'gradedBy:id,user_id',
            'gradedBy.user:id,name,email',
        ]);

        $this->authorize('view', $mark);

        return $this->ok('Retrieved marks', $mark->toArray());
    }

    /**
     * Update student mark
     *
     * Update a specific student's mark and feedback. The mark must not exceed the assessment max mark.
     *
     * @authenticated
     *
     * @urlParam mark integer required The ID of the student mark. Example: 1
     *
     * @bodyParam mark number required The updated mark. Must be between 0 and the assessment max mark. Example: 9
     * @bodyParam feedback string required Feedback for the student. Maximum 1000 characters. Example: Improved answer
     * @bodyParam status string required Mark status. Must be one of: valid, voided, excused, absent, under_review. Example: valid
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Mark updated successfully.",
     *   "data": {
     *     "id": 1,
     *     "course_assessment_id": 1,
     *     "student_id": 3,
     *     "mark": "9.00",
     *     "feedback": "Improved answer",
     *     "graded_by": 2,
     *     "graded_at": "2026-07-08 10:00:00",
     *     "status": "valid",
     *     "created_at": "2026-07-08T09:30:00.000000Z",
     *     "updated_at": "2026-07-08T10:00:00.000000Z"
     *   }
     * }
     * @response 422 {
     *   "message": "The mark must not be greater than the assessment max mark.",
     *   "errors": {
     *     "mark": ["The mark must not be greater than 10."]
     *   }
     * }
     */
    public function update(UpdateStudentMarkRequest $request, StudentMarks $mark)
    {
        $this->authorize('update', $mark);

        $credentials = $request->validated();

        $teacher = $request->user()->teacher;

        $mark->update([
            'mark' => $credentials['mark'],
            'feedback' => $credentials['feedback'],
            'status' => $credentials['status'],
            'graded_by' => $teacher->id,
            'graded_at' => now(),
        ]);

        return $this->ok('Mark updated successfully.', $mark->fresh()->toArray());
    }
}
