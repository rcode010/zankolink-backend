<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseAssessmentRequest;
use App\Http\Requests\SyncCourseAssessmentsRequest;
use App\Http\Requests\UpdateCourseAssessmentRequest;
use App\Models\Course;
use App\Models\CourseAssessments;
use App\Services\StudentCourseGradeCalculator;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Course Assessments
 *
 * APIs for managing course assessments such as quizzes, assignments, midterms, finals, projects, and activities.
 */
class CourseAssesmentsController extends Controller
{
    use ApiResponses;

    /**
     * List course assessments
     *
     * Retrieve all assessments for a specific course. Supports filtering by title and assessment type.
     *
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 1
     *
     * @queryParam filter[title] string Filter assessments by partial title. Example: Quiz
     * @queryParam filter[type] string Filter assessments by exact type. Must be one of: quiz, assignment, final, midterm, project, activity. Example: quiz
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Course Assessments retrieved successfully",
     *   "data": [
     *     {
     *       "id": 1,
     *       "course_id": 1,
     *       "teacher_id": 2,
     *       "academic_year_id": 1,
     *       "title": "Quiz 1",
     *       "type": "quiz",
     *       "max_mark": "10.00",
     *       "weight": "5.00",
     *       "due_at": "2026-07-20 10:00:00",
     *       "is_published": true,
     *       "created_at": "2026-07-08T09:00:00.000000Z",
     *       "updated_at": "2026-07-08T09:00:00.000000Z"
     *     }
     *   ]
     * }
     */
    public function index(Course $course)
    {
        $this->authorize('viewAny', [CourseAssessments::class, $course]);

        $assessments = QueryBuilder::for(CourseAssessments::class)
            ->where('course_id', $course->id)
            ->allowedFilters(
                AllowedFilter::partial('title'),
                AllowedFilter::exact('type'),
            )->get();

        return $this->ok('Course Assessments retrieved successfully', $assessments->toArray());
    }

    /**
     * Create course assessment
     *
     * Create a new assessment for a specific course. The authenticated user must be a teacher.
     *
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 1
     *
     * @bodyParam academic_year_id integer required The ID of the academic year. Example: 1
     * @bodyParam title string required The assessment title. Example: Quiz 1
     * @bodyParam type string required The assessment type. Must be one of: quiz, assignment, final, midterm, project, activity. Example: quiz
     * @bodyParam max_mark number required The maximum mark for this assessment. Example: 10
     * @bodyParam weight number required The assessment weight. Example: 5
     * @bodyParam due_at datetime nullable The due date and time of the assessment. Example: 2026-07-20 10:00:00
     * @bodyParam is_published boolean required Whether the assessment is visible/published. Example: true
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Course Assessment created successfully",
     *   "data": {
     *     "id": 1,
     *     "course_id": 1,
     *     "teacher_id": 2,
     *     "academic_year_id": 1,
     *     "title": "Quiz 1",
     *     "type": "quiz",
     *     "max_mark": "10.00",
     *     "weight": "5.00",
     *     "due_at": "2026-07-20 10:00:00",
     *     "is_published": true,
     *     "created_at": "2026-07-08T09:00:00.000000Z",
     *     "updated_at": "2026-07-08T09:00:00.000000Z"
     *   }
     * }
     * @response 422 {
     *   "message": "The title field is required.",
     *   "errors": {
     *     "title": ["The title field is required."]
     *   }
     * }
     */
    public function store(StoreCourseAssessmentRequest $request, Course $course)
    {
        $this->authorize('create', [CourseAssessments::class, $course]);

        $credentials = $request->validated();
        $teacher = $request->user()->teacher;
        $courseAssessment = CourseAssessments::create([
            'course_id' => $course->id,
            'academic_year_id' => $credentials['academic_year_id'],
            'type' => $credentials['type'],
            'max_mark' => $credentials['max_mark'],
            'title' => $credentials['title'],
            'weight' => $credentials['weight'],
            'due_at' => $credentials['due_at'] ?? null,
            'teacher_id' => $teacher->id,
            'is_published' => $credentials['is_published'] ?? false,
        ]);

        return $this->created('Course Assessment created successfully', $courseAssessment->toArray());

    }

    /**
     * Update course assessment
     *
     * Update an existing course assessment. The assessment must belong to the given course.
     *
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 1
     * @urlParam assessment integer required The ID of the assessment. Example: 1
     *
     * @bodyParam academic_year_id integer optional The ID of the academic year. Example: 1
     * @bodyParam title string optional The assessment title. Example: Midterm Exam
     * @bodyParam type string optional The assessment type. Must be one of: quiz, assignment, final, midterm, project, activity. Example: midterm
     * @bodyParam max_mark number optional The maximum mark for this assessment. Example: 30
     * @bodyParam weight number optional The assessment weight. Example: 20
     * @bodyParam due_at datetime nullable The due date and time of the assessment. Example: 2026-08-01 09:00:00
     * @bodyParam is_published boolean optional Whether the assessment is visible/published. Example: true
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Course Assessment updated successfully",
     *   "data": {
     *     "id": 1,
     *     "course_id": 1,
     *     "teacher_id": 2,
     *     "academic_year_id": 1,
     *     "title": "Midterm Exam",
     *     "type": "midterm",
     *     "max_mark": "30.00",
     *     "weight": "20.00",
     *     "due_at": "2026-08-01 09:00:00",
     *     "is_published": true
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Assessment does not belong to this course."
     * }
     */
    public function update(UpdateCourseAssessmentRequest $request, CourseAssessments $assessment)
    {
        $this->authorize('update', $assessment);

        $credentials = $request->validated();

        $assessment->update($credentials);

        return $this->ok('Course Assessment updated successfully', $assessment->toArray());

    }
    /**
     * Synchronize course assessments
     *
     * Save the final assessment structure for a course and academic year in one request.
     *
     * Existing assessments with an ID are updated, assessments without an ID are created,
     * and existing assessments omitted from the submitted list are soft deleted.
     *
     * The total weight of all submitted assessments must not exceed 100.
     * Student course grades are recalculated after the assessments are synchronized.
     *
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 1
     *
     * @bodyParam academic_year_id integer required The ID of the academic year. Example: 1
     *
     * @bodyParam assessments array required The final list of assessments for the course and academic year.
     *
     * @bodyParam assessments.*.id integer nullable The assessment ID. Provide an existing ID to update an assessment. Send null or omit the ID to create a new assessment. Example: 10
     *
     * @bodyParam assessments.*.title string required The assessment title. Maximum 255 characters. Example: Assignment 2
     *
     * @bodyParam assessments.*.type string required The assessment type. Allowed values: quiz, assignment, final, midterm, project, activity. Example: assignment
     *
     * @bodyParam assessments.*.max_mark number required The maximum mark available for the assessment. Must be greater than 0. Example: 10
     *
     * @bodyParam assessments.*.weight number required The assessment's contribution to the final course grade. Must be between 0 and 100. The combined weight of all assessments must not exceed 100. Example: 10
     *
     * @bodyParam assessments.*.due_at datetime nullable The assessment due date and time. Example: 2026-07-30 23:59:00
     *
     * @bodyParam assessments.*.is_published boolean required Whether the assessment is visible to students. Example: true
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Course assessments saved successfully.",
     *   "data": [
     *     {
     *       "id": 10,
     *       "course_id": 1,
     *       "teacher_id": 1,
     *       "academic_year_id": 1,
     *       "title": "Assignment 2",
     *       "type": "assignment",
     *       "max_mark": 10,
     *       "weight": 10,
     *       "due_at": null,
     *       "is_published": 1,
     *       "created_at": "2026-07-15T07:34:24.000000Z",
     *       "updated_at": "2026-07-15T07:44:13.000000Z",
     *       "deleted_at": null
     *     },
     *     {
     *       "id": 16,
     *       "course_id": 1,
     *       "teacher_id": 1,
     *       "academic_year_id": 1,
     *       "title": "Assignment 1",
     *       "type": "assignment",
     *       "max_mark": 10,
     *       "weight": 10,
     *       "due_at": null,
     *       "is_published": 1,
     *       "created_at": "2026-07-15T07:42:28.000000Z",
     *       "updated_at": "2026-07-15T07:44:13.000000Z",
     *       "deleted_at": null
     *     },
     *     {
     *       "id": 22,
     *       "course_id": 1,
     *       "teacher_id": 1,
     *       "academic_year_id": 1,
     *       "title": "Midterm",
     *       "type": "midterm",
     *       "max_mark": 30,
     *       "weight": 25,
     *       "due_at": null,
     *       "is_published": 1,
     *       "created_at": "2026-07-15T07:43:24.000000Z",
     *       "updated_at": "2026-07-15T07:44:13.000000Z",
     *       "deleted_at": null
     *     },
     *     {
     *       "id": 26,
     *       "course_id": 1,
     *       "teacher_id": 1,
     *       "academic_year_id": 1,
     *       "title": "Activity 5",
     *       "type": "activity",
     *       "max_mark": 10,
     *       "weight": 5,
     *       "due_at": null,
     *       "is_published": 0,
     *       "created_at": "2026-07-15T07:44:13.000000Z",
     *       "updated_at": "2026-07-15T07:44:13.000000Z",
     *       "deleted_at": null
     *     }
     *   ]
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "assessments": [
     *       "The total assessment weight may not exceed 100."
     *     ]
     *   }
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "assessments": [
     *       "One or more assessments do not belong to this course and academic year."
     *     ]
     *   }
     * }
     *
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "assessments": [
     *       "The same assessment cannot appear more than once."
     *     ]
     *   }
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Teacher profile not found."
     * }
     */
    public function syncAssessments(SyncCourseAssessmentsRequest $request, Course $course, StudentCourseGradeCalculator $gradeCalculator) {
        $validated = $request->validated();

        $academicYearId = (int) $validated['academic_year_id'];

        $createItems = collect($validated['create'] ?? []);
        $updateItems = collect($validated['update'] ?? []);

        $deleteIds = collect($validated['delete'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $updateIds = $updateItems
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $referencedIds = $updateIds
            ->merge($deleteIds)
            ->unique()
            ->values();


        $existingAssessments = CourseAssessments::query()
            ->where('course_id', $course->id)
            ->where('academic_year_id', $academicYearId)
            ->whereIn('id', $referencedIds)
            ->get()
            ->keyBy('id');

        if (
            $existingAssessments->count()
            !== $referencedIds->count()
        ) {
            return $this->error(
                'One or more assessments do not belong to this course and academic year.',
                422
            );
        }

        if ($createItems->isNotEmpty()) {
            $this->authorize(
                'create',
                [CourseAssessments::class, $course]
            );

            $teacher = $request->user()->teacher;

            if (! $teacher) {
                return $this->error(
                    'Teacher profile not found.',
                    404
                );
            }
        }


        foreach ($updateIds as $assessmentId) {
            $this->authorize(
                'update',
                $existingAssessments->get($assessmentId)
            );
        }

        foreach ($deleteIds as $assessmentId) {
            $this->authorize(
                'delete',
                $existingAssessments->get($assessmentId)
            );
        }

        $now = now();

        $updateRows = $updateItems
            ->map(function (array $item) use (
                $existingAssessments,
                $now
            ) {
                $assessment = $existingAssessments->get(
                    (int) $item['id']
                );

                return [
                    'id' => $assessment->id,
                    'course_id' => $assessment->course_id,
                    'teacher_id' => $assessment->teacher_id,
                    'academic_year_id' =>
                        $assessment->academic_year_id,

                    'title' => array_key_exists('title', $item)
                        ? $item['title']
                        : $assessment->title,

                    'type' => array_key_exists('type', $item)
                        ? $item['type']
                        : $assessment->type,

                    'max_mark' => array_key_exists(
                        'max_mark',
                        $item
                    )
                        ? $item['max_mark']
                        : $assessment->max_mark,

                    'weight' => array_key_exists('weight', $item)
                        ? $item['weight']
                        : $assessment->weight,

                    'due_at' => array_key_exists('due_at', $item)
                        ? $item['due_at']
                        : $assessment->due_at,

                    'is_published' => array_key_exists(
                        'is_published',
                        $item
                    )
                        ? $item['is_published']
                        : $assessment->is_published,

                    'created_at' => $assessment->created_at,
                    'updated_at' => $now,
                ];
            })
            ->values();

        $createRows = $createItems
            ->map(fn (array $item) => [
                'course_id' => $course->id,
                'teacher_id' => $teacher->id,
                'academic_year_id' => $academicYearId,
                'title' => $item['title'],
                'type' => $item['type'],
                'max_mark' => $item['max_mark'],
                'weight' => $item['weight'],
                'due_at' => $item['due_at'] ?? null,
                'is_published' => $item['is_published'],
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values();

        $shouldRecalculateGrades =
            $deleteIds->isNotEmpty()
            || $updateItems->contains(
                fn (array $item) =>
                    array_key_exists('weight', $item)
                    || array_key_exists('max_mark', $item)
            );

        DB::transaction(function () use (
            $updateRows,
            $createRows,
            $deleteIds,
            $shouldRecalculateGrades,
            $course,
            $academicYearId,
            $gradeCalculator
        ) {
            if ($updateRows->isNotEmpty()) {
                CourseAssessments::query()->upsert(
                    $updateRows->all(),
                    ['id'],
                    [
                        'title',
                        'type',
                        'max_mark',
                        'weight',
                        'due_at',
                        'is_published',
                        'updated_at',
                    ]
                );
            }

            if ($createRows->isNotEmpty()) {
                CourseAssessments::query()->insert(
                    $createRows->all()
                );
            }

            if ($deleteIds->isNotEmpty()) {
                CourseAssessments::query()
                    ->where('course_id', $course->id)
                    ->where(
                        'academic_year_id',
                        $academicYearId
                    )
                    ->whereIn('id', $deleteIds)
                    ->delete();
            }

            if ($shouldRecalculateGrades) {
                $studentIds = DB::table('course_student')
                    ->where('course_id', $course->id)
                    ->where(
                        'academic_year_id',
                        $academicYearId
                    )
                    ->pluck('student_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $gradeCalculator->recalculateForCourse(
                    $course,
                    $academicYearId,
                    $studentIds
                );
            }
        });

        $assessments = CourseAssessments::query()
            ->where('course_id', $course->id)
            ->where('academic_year_id', $academicYearId)
            ->orderBy('id')
            ->get();

        return $this->ok(
            'Course assessments saved successfully.',
            $assessments->toArray()
        );
    }
    /**
     * Show course assessment
     *
     * Retrieve a specific course assessment with its course, teacher, and academic year details.
     *
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 1
     * @urlParam assessment integer required The ID of the assessment. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Course Assessment retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "course_id": 1,
     *     "teacher_id": 2,
     *     "academic_year_id": 1,
     *     "title": "Quiz 1",
     *     "type": "quiz",
     *     "max_mark": "10.00",
     *     "weight": "5.00",
     *     "due_at": "2026-07-20 10:00:00",
     *     "is_published": true,
     *     "course": {
     *       "id": 1,
     *       "name": "Database Systems"
     *     },
     *     "teacher": {
     *       "id": 2,
     *       "user_id": 5,
     *       "user": {
     *         "id": 5,
     *         "name": "Teacher Name"
     *       }
     *     },
     *     "academic_year": {
     *       "id": 1,
     *       "year": "2026-2027"
     *     }
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Assessment does not belong to this course."
     * }
     */
    public function show(Course $course, CourseAssessments $assessment)
    {
        $this->authorize('view', $assessment);

        $assessment->load([
            'course:id,name',
            'teacher:id,user_id',
            'teacher.user:id,name',
            'academicYear:id,year',
        ]);

        return $this->ok(
            'Course Assessment retrieved successfully',
            $assessment->toArray()
        );
    }

    /**
     * Delete course assessment
     *
     * Soft delete a specific course assessment. The assessment must belong to the given course.
     *
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 1
     * @urlParam assessment integer required The ID of the assessment. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Course Assessment deleted successfully"
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Assessment does not belong to this course."
     * }
     */
    public function destroy(Course $course, CourseAssessments $assessment)
    {
        $this->authorize('delete', $assessment);

        $assessment->delete();

        return $this->deleted('Course Assessment deleted successfully');
    }
}
