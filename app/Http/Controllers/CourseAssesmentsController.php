<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkUpdateCourseAssessmentsRequest;
use App\Http\Requests\StoreCourseAssessmentRequest;
use App\Http\Requests\SyncCourseAssessmentsRequest;
use App\Http\Requests\UpdateCourseAssessmentRequest;
use App\Models\Course;
use App\Models\CourseAssessments;
use App\Services\StudentCourseGradeCalculator;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
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
    public function index(Request $request, Course $course)
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
    public function update(UpdateCourseAssessmentRequest $request, Course $course, CourseAssessments $assessment)
    {
        $this->authorize('update', $assessment);

        $credentials = $request->validated();

        $assessment->update($credentials);

        return $this->ok('Course Assessment updated successfully', $assessment->toArray());

    }
    /**
     * Bulk update course assessments
     *
     * Update multiple assessments belonging to the same course in one request.
     * Each assessment object must include its assessment ID and at least one field to update.
     *
     * @group Course Assessments
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 1
     *
     * @bodyParam assessments array required The assessments to update.
     * @bodyParam assessments.*.id integer required The ID of the assessment. Example: 1
     * @bodyParam assessments.*.academic_year_id integer optional The academic year ID. Example: 1
     * @bodyParam assessments.*.title string optional The assessment title. Example: Updated Quiz 1
     * @bodyParam assessments.*.type string optional The assessment type. Must be one of: quiz, assignment, final, midterm, project, activity. Example: midterm
     * @bodyParam assessments.*.max_mark number optional The maximum assessment mark. Example: 20
     * @bodyParam assessments.*.weight number optional The assessment weight. Example: 10
     * @bodyParam assessments.*.due_at datetime nullable The assessment due date and time. Example: 2026-07-20 10:00:00
     * @bodyParam assessments.*.is_published boolean optional Whether the assessment is published. Example: true
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Course assessments updated successfully.",
     *   "data": [
     *     {
     *       "id": 1,
     *       "course_id": 1,
     *       "teacher_id": 91,
     *       "academic_year_id": 1,
     *       "title": "Updated Quiz 1",
     *       "type": "midterm",
     *       "max_mark": 20,
     *       "weight": 10,
     *       "due_at": "2026-07-20 10:00:00",
     *       "is_published": 1,
     *       "created_at": "2026-07-11T19:20:42.000000Z",
     *       "updated_at": "2026-07-11T19:25:17.000000Z",
     *       "deleted_at": null
     *     },
     *     {
     *       "id": 2,
     *       "course_id": 1,
     *       "teacher_id": 91,
     *       "academic_year_id": 1,
     *       "title": "Updated Midterm",
     *       "type": "midterm",
     *       "max_mark": 30,
     *       "weight": 30,
     *       "due_at": "2026-08-20 10:00:00",
     *       "is_published": 0,
     *       "created_at": "2026-07-11T19:20:44.000000Z",
     *       "updated_at": "2026-07-11T19:25:17.000000Z",
     *       "deleted_at": null
     *     }
     *   ]
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "One or more assessments do not belong to this course."
     * }
     *
     * @response 422 {
     *   "message": "The assessments field is required.",
     *   "errors": {
     *     "assessments": [
     *       "The assessments field is required."
     *     ]
     *   }
     * }
     */
    public function syncAssessments(SyncCourseAssessmentsRequest $request, Course $course, StudentCourseGradeCalculator $gradeCalculator) {
        $validated = $request->validated();

        $academicYearId = (int) $validated['academic_year_id'];
        $items = collect($validated['assessments']);

        $teacher = $request->user()->teacher;

        if (! $teacher) {
            return $this->error('Teacher profile not found.', 404);
        }

        $existingIds = $items
            ->pluck('id')
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
            ->values();

        $existingAssessments = CourseAssessments::query()
            ->where('course_id', $course->id)
            ->where('academic_year_id', $academicYearId)
            ->whereIn('id', $existingIds)
            ->get()
            ->keyBy('id');

        if ($existingAssessments->count() !== $existingIds->count()) {
            return $this->error(
                'One or more assessments do not belong to this course and academic year.',
                422
            );
        }

        foreach ($existingAssessments as $assessment) {
            $this->authorize('update', $assessment);
        }

        $newItems = $items->filter(
            fn (array $item) => empty($item['id'])
        );

        if ($newItems->isNotEmpty()) {
            $this->authorize(
                'create',
                [CourseAssessments::class, $course]
            );
        }


        $deleteQuery = CourseAssessments::query()
            ->where('course_id', $course->id)
            ->where('academic_year_id', $academicYearId);

        if ($existingIds->isNotEmpty()) {
            $deleteQuery->whereNotIn('id', $existingIds);
        }

        $assessmentsToDelete = $deleteQuery->get();

        foreach ($assessmentsToDelete as $assessment) {
            $this->authorize('delete', $assessment);
        }

        $now = now();

        DB::transaction(function () use (
            $items,
            $existingIds,
            $assessmentsToDelete,
            $course,
            $academicYearId,
            $teacher,
            $gradeCalculator,
            $now
        ) {

            $updateRows = $items
                ->filter(fn (array $item) => ! empty($item['id']))
                ->map(fn (array $item) => [
                    'id' => (int) $item['id'],
                    'course_id' => $course->id,
                    'teacher_id' => $teacher->id,
                    'academic_year_id' => $academicYearId,
                    'title' => $item['title'],
                    'type' => $item['type'],
                    'max_mark' => $item['max_mark'],
                    'weight' => $item['weight'],
                    'due_at' => $item['due_at'],
                    'is_published' => $item['is_published'],
                    'updated_at' => $now,
                ])
                ->values();

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
//            dd("here...");


            $insertRows = $items
                ->filter(fn (array $item) => empty($item['id']))
                ->map(fn (array $item) => [
                    'course_id' => $course->id,
                    'teacher_id' => $teacher->id,
                    'academic_year_id' => $academicYearId,
                    'title' => $item['title'],
                    'type' => $item['type'],
                    'max_mark' => $item['max_mark'],
                    'weight' => $item['weight'],
                    'due_at' => $item['due_at'],
                    'is_published' => $item['is_published'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->values();

            if ($insertRows->isNotEmpty()) {
                CourseAssessments::query()->insert(
                    $insertRows->all()
                );
            }

            if ($assessmentsToDelete->isNotEmpty()) {
                CourseAssessments::query()
                    ->whereKey($assessmentsToDelete->modelKeys())
                    ->delete();
            }


            $studentIds = DB::table('course_student')
                ->where('course_id', $course->id)
                ->where('academic_year_id', $academicYearId)
                ->pluck('student_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $gradeCalculator->recalculateForCourse(
                $course,
                $academicYearId,
                $studentIds
            );
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
