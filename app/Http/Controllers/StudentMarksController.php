<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetGradebookRequest;
use App\Http\Requests\StoreGradebookMarksRequest;
use App\Http\Requests\StoreStudentMarkRequest;
use App\Http\Requests\UpdateStudentMarkRequest;
use App\Models\Course;
use App\Models\CourseAssessments;
use App\Models\StudentMarks;
use App\Services\StudentCourseGradeCalculator;
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
    public function store(StoreStudentMarkRequest $request, CourseAssessments $assessment, StudentCourseGradeCalculator $studentCourseGrade)
    {
        $this->authorize('create', [StudentMarks::class, $assessment]);

        $teacher = $request->user()->teacher;
        if (! $teacher) {
            return $this->error('Teacher profile not found.', 404);
        }
        $validatedMarks = collect($request->validated()['marks']);

        $studentIds = $validatedMarks
            ->pluck('student_id')
            ->unique()
            ->values()
            ->all();
        $marks = $validatedMarks
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

        DB::transaction(function () use (
            $marks,
            $assessment,
            $studentIds,
            $studentCourseGrade
        ) {
            DB::table('student_marks')->upsert(
                $marks,
                ['course_assessment_id', 'student_id'],
                [
                    'mark',
                    'feedback',
                    'status',
                    'graded_by',
                    'graded_at',
                    'updated_at',
                ]
            );

            $studentCourseGrade->recalculateForAssessment(
                $assessment,
                $studentIds
            );
        });

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
            'courseAssessment:id,course_id,teacher_id,title,max_mark',
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
    public function update(
        UpdateStudentMarkRequest $request,
        StudentMarks $mark,
        StudentCourseGradeCalculator $studentCourseGrade
    ) {
        $this->authorize('update', $mark);

        $credentials = $request->validated();
        $teacher = $request->user()->teacher;
        if (! $teacher) {
            return $this->error('Teacher profile not found.', 404);
        }

        $assessment = $mark->courseAssessment;

        DB::transaction(function () use ($mark, $credentials, $teacher, $assessment, $studentCourseGrade) {
            $mark->update([
                'mark' => $credentials['mark'],
                'feedback' => $credentials['feedback'],
                'status' => $credentials['status'],
                'graded_by' => $teacher->id,
                'graded_at' => now(),
            ]);

            $studentCourseGrade->recalculateForAssessment(
                $assessment,
                [$mark->student_id]
            );
        });

        return $this->ok(
            'Mark updated successfully.',
            $mark->fresh()->toArray()
        );
    }

    /**
     * Get course gradebook
     *
     * Retrieves all assessments for the selected course and academic year,
     * together with the enrolled students and their marks for every assessment.
     *
     * Students who have not been graded are still returned with null mark,
     * status, and feedback values.
     *
     * The total_grade value represents the student's automatically calculated
     * weighted grade for the course.
     *
     * @group Student Marks
     *
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 1
     *
     * @queryParam academic_year_id integer required The ID of the academic year. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Retrieved gradebook",
     *   "data": {
     *     "assessments": [
     *       {
     *         "id": 1,
     *         "title": "Quiz",
     *         "max_mark": 20,
     *         "weight": 30
     *       },
     *       {
     *         "id": 2,
     *         "title": "Assignment 1",
     *         "max_mark": 10,
     *         "weight": 10
     *       }
     *     ],
     *     "students": [
     *       {
     *         "id": 361,
     *         "name": "Demo Student One",
     *         "total_grade": null,
     *         "enrollment_status": "enrolled",
     *         "marks": [
     *           {
     *             "assessment_id": 1,
     *             "mark": null,
     *             "status": null,
     *             "feedback": null
     *           },
     *           {
     *             "assessment_id": 2,
     *             "mark": null,
     *             "status": null,
     *             "feedback": null
     *           }
     *         ]
     *       },
     *       {
     *         "id": 3,
     *         "name": "Student 003",
     *         "total_grade": 72,
     *         "enrollment_status": "enrolled",
     *         "marks": [
     *           {
     *             "assessment_id": 1,
     *             "mark": 16,
     *             "status": "valid",
     *             "feedback": "Excellent work."
     *           },
     *           {
     *             "assessment_id": 2,
     *             "mark": 8,
     *             "status": "valid",
     *             "feedback": null
     *           }
     *         ]
     *       }
     *     ]
     *   }
     * }
     * @response 422 {
     *   "message": "The academic year id field is required.",
     *   "errors": {
     *     "academic_year_id": [
     *       "The academic year id field is required."
     *     ]
     *   }
     * }
     */
    public function gradeBook(GetGradebookRequest $request, Course $course)
    {
        $this->authorize('viewGradeBook', [StudentMarks::class, $course]);
        $user = auth()->user();
        $credentials = $request->validated();

        $assessments = CourseAssessments::query()
            ->where('course_id', $course->id)
            ->where('academic_year_id', $credentials['academic_year_id'])
            ->orderBy('id')
            ->get([
                'id',
                'title',
                'max_mark',
                'weight',
                'is_published',
            ]);

        if (! $user->teacher) {
            $assessments = $assessments->where('is_published', true);
        }

        $rows = DB::table('course_student')
            ->join(
                'students',
                'students.id',
                '=',
                'course_student.student_id'
            )
            ->join(
                'users',
                'users.id',
                '=',
                'students.user_id'
            )
            ->leftJoin('student_marks', function ($join) use ($assessments) {
                $join->on(
                    'student_marks.student_id',
                    '=',
                    'students.id'
                );

                if ($assessments->isNotEmpty()) {
                    $join->whereIn(
                        'student_marks.course_assessment_id',
                        $assessments->pluck('id')->all()
                    );
                } else {
                    $join->whereRaw('1 = 0');
                }
            })
            ->where('course_student.course_id', $course->id)
            ->where(
                'course_student.academic_year_id',
                $credentials['academic_year_id']
            )
            ->select([
                'students.id as student_id',
                'users.name as student_name',

                'course_student.grade as total_grade',
                'course_student.status as enrollment_status',

                'student_marks.course_assessment_id as assessment_id',
                'student_marks.mark',
                'student_marks.status as mark_status',
                'student_marks.feedback',
            ])
            ->orderBy('users.name')
            ->get();

        $students = $rows
            ->groupBy('student_id')
            ->map(function ($studentRows) use ($assessments) {
                $student = $studentRows->first();

                return [
                    'id' => $student->student_id,
                    'name' => $student->student_name,
                    'total_grade' => $student->total_grade !== null
                        ? (float) $student->total_grade
                        : null,
                    'enrollment_status' => $student->enrollment_status,

                    'marks' => $assessments
                        ->map(function ($assessment) use ($studentRows) {
                            $mark = $studentRows->firstWhere(
                                'assessment_id',
                                $assessment->id
                            );

                            return [
                                'assessment_id' => $assessment->id,
                                'mark' => $mark?->mark !== null
                                    ? (float) $mark->mark
                                    : null,
                                'status' => $mark?->mark_status,
                                'feedback' => $mark?->feedback,
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();

        return $this->ok('Retrieved gradebook',
            [
                'assessments' => $assessments->map(function ($assessment) {
                    return [
                        'id' => $assessment->id,
                        'title' => $assessment->title,
                        'max_mark' => (float) $assessment->max_mark,
                        'weight' => $assessment->weight !== null
                            ? (float) $assessment->weight
                            : null,
                        'is_published' => $assessment->is_published,
                    ];
                })->values(),

                'students' => $students,
            ]
        );
    }

    public function storeGradeBook(StoreGradebookMarksRequest $request, Course $course, StudentCourseGradeCalculator $gradeCalculator)
    {
        $this->authorize('storeGradeBook', [StudentMarks::class, $course]);

        $teacher = $request->user()->teacher;

        $validated = $request->validated();

        $academicYearId = (int) $validated['academic_year_id'];
        $now = now();

        $marks = collect($validated['marks'])
            ->map(fn (array $mark) => [
                'course_assessment_id' => (int) $mark['assessment_id'],
                'student_id' => (int) $mark['student_id'],
                'mark' => $mark['mark'] !== null
                    ? (float) $mark['mark']
                    : null,
                'feedback' => $mark['feedback'] ?? null,
                'graded_by' => $teacher->id,
                'status' => $mark['status'] ?? 'valid',
                'graded_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values();

        $studentIds = $marks
            ->pluck('student_id')
            ->unique()
            ->values()
            ->all();

        DB::transaction(function () use ($marks, $course, $academicYearId, $studentIds, $gradeCalculator) {

            DB::table('student_marks')->upsert(
                $marks->all(),
                [
                    'course_assessment_id',
                    'student_id',
                ],
                [
                    'mark',
                    'feedback',
                    'status',
                    'graded_by',
                    'graded_at',
                    'updated_at',
                ]
            );

            $gradeCalculator->recalculateForCourse(
                $course,
                $academicYearId,
                $studentIds
            );
        });

        return $this->ok('Gradebook marks saved successfully.', [
            'saved_marks_count' => $marks->count(),
            'recalculated_students_count' => count($studentIds),
        ]);
    }
}
