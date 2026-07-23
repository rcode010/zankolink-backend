<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublishAssessmentsRequest;
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
     * Retrieve all assessments for a specific course. Supports filtering by title.
     *
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 1
     *
     * @queryParam filter[title] string Filter assessments by partial title. Example: Quiz
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
     *       "max_mark": "10.00",
     *       "weight": "5.00",
     *       "due_at": "2026-07-20 10:00:00",
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
     * @bodyParam max_mark number required The maximum mark for this assessment. Example: 10
     * @bodyParam weight number required The assessment weight. Example: 5
     * @bodyParam due_at datetime nullable The due date and time of the assessment. Example: 2026-07-20 10:00:00
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
     *     "max_mark": "10.00",
     *     "weight": "5.00",
     *     "due_at": "2026-07-20 10:00:00",
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
     * @bodyParam max_mark number optional The maximum mark for this assessment. Example: 30
     * @bodyParam weight number optional The assessment weight. Example: 20
     * @bodyParam due_at datetime nullable The due date and time of the assessment. Example: 2026-08-01 09:00:00
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
     *     "max_mark": "30.00",
     *     "weight": "20.00",
     *     "due_at": "2026-08-01 09:00:00",
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
     * Apply bulk course assessment changes
     *
     * Create new assessments, update existing assessments, and delete selected
     * assessments for the same course and academic year in one request.
     *
     * Only assessments included in the create, update, or delete operations are
     * changed. Assessments not included in the request remain unchanged.
     *
     * All operations are executed inside one database transaction. If any
     * operation fails, all changes are rolled back.
     *
     * Student course grades are recalculated when an assessment is deleted or
     * when an assessment's maximum mark or weight is updated.
     *
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 1
     *
     * @bodyParam academic_year_id integer required The ID of the academic year. Example: 1
     * @bodyParam create array optional Assessments that should be created.
     * @bodyParam create.*.title string required The assessment title. Maximum 255 characters. Example: Activity 5
     * @bodyParam create.*.max_mark number required The maximum available mark. Must be greater than 0. Example: 10
     * @bodyParam create.*.weight number required The assessment's contribution to the final course grade. Must be between 0 and 100. Example: 5
     * @bodyParam create.*.due_at datetime nullable The assessment due date and time. Example: 2026-08-01 10:00:00
     * @bodyParam update array optional Existing assessments that should be updated.
     * @bodyParam update.*.id integer required The ID of the assessment that should be updated. Example: 1
     * @bodyParam update.*.title string optional The updated assessment title. Maximum 255 characters. Example: Updated Midterm
     * @bodyParam update.*.max_mark number optional The updated maximum available mark. Must be greater than 0. Example: 20
     * @bodyParam update.*.weight number optional The updated assessment weight. Must be between 0 and 100. Example: 30
     * @bodyParam update.*.due_at datetime nullable The updated due date and time. Send null to remove the due date. Example: 2026-08-01 10:00:00
     * @bodyParam delete array optional IDs of assessments that should be soft deleted. Example: [3,4]
     * @bodyParam delete.* integer required The ID of an assessment that should be deleted. Example: 3
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Course assessments saved successfully.",
     *   "data": [
     *     {
     *       "id": 1,
     *       "course_id": 1,
     *       "teacher_id": 5,
     *       "academic_year_id": 1,
     *       "title": "asdf 2 sdf",
     *       "max_mark": 20,
     *       "weight": 30,
     *       "due_at": "2026-08-01 10:00:00",
     *       "created_at": "2026-07-15T10:50:03.000000Z",
     *       "updated_at": "2026-07-15T10:54:09.000000Z",
     *       "deleted_at": null
     *     },
     *     {
     *       "id": 2,
     *       "course_id": 1,
     *       "teacher_id": 5,
     *       "academic_year_id": 1,
     *       "title": "Quiz",
     *       "max_mark": 20,
     *       "weight": 30,
     *       "due_at": "2026-08-01 10:00:00",
     *       "created_at": "2026-07-15T10:50:03.000000Z",
     *       "updated_at": "2026-07-15T10:53:57.000000Z",
     *       "deleted_at": null
     *     },
     *     {
     *       "id": 5,
     *       "course_id": 1,
     *       "teacher_id": 5,
     *       "academic_year_id": 1,
     *       "title": "Activity 5",
     *       "max_mark": 10,
     *       "weight": 5,
     *       "due_at": null,
     *       "created_at": "2026-07-15T10:53:01.000000Z",
     *       "updated_at": "2026-07-15T10:53:01.000000Z",
     *       "deleted_at": null
     *     },
     *     {
     *       "id": 6,
     *       "course_id": 1,
     *       "teacher_id": 5,
     *       "academic_year_id": 1,
     *       "title": "asdf 5",
     *       "max_mark": 10,
     *       "weight": 5,
     *       "due_at": null,
     *       "created_at": "2026-07-15T10:53:26.000000Z",
     *       "updated_at": "2026-07-15T10:53:26.000000Z",
     *       "deleted_at": null
     *     },
     *     {
     *       "id": 7,
     *       "course_id": 1,
     *       "teacher_id": 5,
     *       "academic_year_id": 1,
     *       "title": "asdf 5",
     *       "max_mark": 10,
     *       "weight": 30,
     *       "due_at": null,
     *       "created_at": "2026-07-15T10:53:37.000000Z",
     *       "updated_at": "2026-07-15T10:54:09.000000Z",
     *       "deleted_at": null
     *     }
     *   ]
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "operations": [
     *       "At least one create, update, or delete operation is required."
     *     ]
     *   }
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "operations": [
     *       "The same assessment cannot be updated and deleted in one request."
     *     ]
     *   }
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "operations": [
     *       "One or more assessments do not belong to this course and academic year."
     *     ]
     *   }
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "operations": [
     *       "The final total assessment weight may not exceed 100."
     *     ]
     *   }
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "update.0": [
     *       "At least one assessment field must be provided for update."
     *     ]
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Teacher profile not found."
     * }
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     */
    public function syncAssessments(SyncCourseAssessmentsRequest $request, Course $course, StudentCourseGradeCalculator $gradeCalculator)
    {
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
                    'academic_year_id' => $assessment->academic_year_id,

                    'title' => array_key_exists('title', $item)
                        ? $item['title']
                        : $assessment->title,

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
                fn (array $item) => array_key_exists('weight', $item)
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

    public function publishAssessments(PublishAssessmentsRequest $request, Course $course)
    {
        $course->assessments()->update([
            'is_published' => $request->boolean('is_published'),
        ]);

        return $this->ok(
            'Course assessments sent to Head of Department successfully.',
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
     *     "max_mark": "10.00",
     *     "weight": "5.00",
     *     "due_at": "2026-07-20 10:00:00",
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
