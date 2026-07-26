<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\Department;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Coruse
 *
 * APIs for Course CRUD.
 */
class CourseController extends Controller
{
    use ApiResponses;

    /**
     * Create a Course
     *
     * Creates a new course for a department. The authenticated user must have
     * permission to create courses and be authorized for the specified department.
     *
     * @authenticated
     *
     * @bodyParam name string required The name of the course. Example: Database Systems
     * @bodyParam code string required The course code. Example: CSe322
     * @bodyParam credit_hours integer required Number of credit hours. Example: 3
     * @bodyParam year_level integer required The year level this course belongs to. Example: 3
     * @bodyParam department_id integer required The ID of the department this course belongs to. Example: 19
     * @bodyParam is_active boolean optional Whether the course is active. Defaults to true. Example: true
     * @bodyParam prerequisites integer[] optional List of prerequisite course IDs. Example: [2, 3, 4]
     *
     * @response 201 scenario="Course created successfully" {
     *   "success": true,
     *   "message": "Course created successfully.",
     *   "data": {
     *     "id": 91,
     *     "name": "Database Systems",
     *     "code": "CSe322",
     *     "credit_hours": 3,
     *     "semester": "fall"
     *     "year_level": 3,
     *     "is_active": true,
     *     "department_id": 19,
     *     "department": {
     *       "id": 19,
     *       "name": "Information Technology"
     *     },
     *     "prerequisites": [
     *       {
     *         "id": 2,
     *         "name": "Cyber Security",
     *         "code": "KOU69619"
     *       },
     *       {
     *         "id": 3,
     *         "name": "Computer Networks",
     *         "code": "SUE41780"
     *       },
     *       {
     *         "id": 4,
     *         "name": "Mobile Application Development",
     *         "code": "KOU74428"
     *       }
     *     ],
     *     "created_at": "2026-07-09 14:33:59",
     *     "updated_at": "2026-07-09 14:33:59"
     *   }
     * }
     * @response 403 scenario="Unauthorized" {
     *   "message": "This action is unauthorized."
     * }
     * @response 404 scenario="Department not found" {
     *   "message": "No query results for model [App\\Models\\Department] 1"
     * }
     * @response 422 scenario="Validation error" {
     *   "message": "The name field is required.",
     *   "errors": {
     *     "name": ["The name field is required."]
     *   }
     * }
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Course::class);


        $query = Course::query();

        $user = $request->user();

        if (! $user->hasRole('MINISTRY_ADMIN')) {
            $scope = $user->userScopes()
                ->where('scope_type', 'DEPARTMENT')
                ->firstOrFail();

            $query->where('department_id', $scope->scope_id);
        }

        $courses = QueryBuilder::for($query)
            ->with(['prerequisites', 'department:id,name'])
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::partial('code'),
                AllowedFilter::exact('department_id'),
                AllowedFilter::exact('semester'),
                'is_active',
            )
            ->latest()
            ->get();

        return $this->ok(
            'Courses retrieved successfully.',
            CourseResource::collection($courses)
                ->response()
                ->getData(true)
        );
    }

    /**
     * Create a Course
     *
     * Creates a new course for a department. The authenticated user must have
     * permission to create courses and be authorized for the specified department.
     *
     * @authenticated
     *
     * @bodyParam name string required The name of the course. Example: Database Systems
     * @bodyParam code string required The course code. Example: CSe322
     * @bodyParam credit_hours integer required Number of credit hours. Example: 3
     * @bodyParam semester required. Example: spring or fall
     * @bodyParam year_level integer required The year level this course belongs to. Example: 3
     * @bodyParam department_id integer required The ID of the department this course belongs to. Example: 19
     * @bodyParam is_active boolean optional Whether the course is active. Defaults to true. Example: true
     * @bodyParam prerequisites integer[] optional List of prerequisite course IDs. Example: [2, 3, 4]
     *
     * @response 201 scenario="Course created successfully" {
     *   "success": true,
     *   "message": "Course created successfully.",
     *   "data": {
     *     "id": 91,
     *     "name": "Database Systems",
     *     "code": "CSe322",
     *     "credit_hours": 3,
     *     "semester": "fall"
     *     "year_level": 3,
     *     "is_active": true,
     *     "department_id": 19,
     *     "department": {
     *       "id": 19,
     *       "name": "Information Technology"
     *     },
     *     "prerequisites": [
     *       {
     *         "id": 2,
     *         "name": "Cyber Security",
     *         "code": "KOU69619"
     *       },
     *       {
     *         "id": 3,
     *         "name": "Computer Networks",
     *         "code": "SUE41780"
     *       },
     *       {
     *         "id": 4,
     *         "name": "Mobile Application Development",
     *         "code": "KOU74428"
     *       }
     *     ],
     *     "created_at": "2026-07-09 14:33:59",
     *     "updated_at": "2026-07-09 14:33:59"
     *   }
     * }
     * @response 403 scenario="Unauthorized" {
     *   "message": "This action is unauthorized."
     * }
     * @response 404 scenario="Department not found" {
     *   "message": "No query results for model [App\\Models\\Department] 1"
     * }
     * @response 422 scenario="Validation error" {
     *   "message": "The name field is required.",
     *   "errors": {
     *     "name": ["The name field is required."]
     *   }
     * }
     */
    public function store(StoreCourseRequest $request)
    {
        $this->authorize('create', Course::class);
        $validated = $request->validated();
        $department = Department::findOrFail($validated['department_id']);

        $this->authorize('createForDepartment', [Course::class, $department]);
        $prerequisites = $validated['prerequisites'] ?? [];
        unset($validated['prerequisites']);
        $course = DB::transaction(function () use ($validated, $prerequisites) {

            $course = Course::create(
                $validated
            );
            $course->prerequisites()->sync($prerequisites);

            return $course;
        });
        $course->load([
            'department:id,name',
            'prerequisites:id,name,code',
        ]);

        return $this->success(
            'Course created successfully.',
            (new CourseResource($course))
                ->toArray($request),
            201
        );
    }

    /**
     * Get a Course
     *
     * Returns a single course by ID with its department and prerequisites.
     *
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 91
     *
     * @response 200 scenario="Course retrieved successfully" {
     *   "success": true,
     *   "message": "Course retrieved successfully.",
     *   "data": {
     *     "id": 91,
     *     "name": "Database Systems",
     *     "code": "CSe322",
     *     "credit_hours": 3,
     *     "semester": "fall"
     *     "year_level": 3,
     *     "is_active": 1,
     *     "department_id": 19,
     *     "department": {
     *       "id": 19,
     *       "name": "Information Technology"
     *     },
     *     "prerequisites": [
     *       {
     *         "id": 2,
     *         "name": "Cyber Security",
     *         "code": "KOU69619"
     *       },
     *       {
     *         "id": 3,
     *         "name": "Computer Networks",
     *         "code": "SUE41780"
     *       },
     *       {
     *         "id": 4,
     *         "name": "Mobile Application Development",
     *         "code": "KOU74428"
     *       }
     *     ],
     *     "created_at": "2026-07-09 14:33:59",
     *     "updated_at": "2026-07-09 14:33:59"
     *   }
     * }
     * @response 403 scenario="Unauthorized" {
     *   "message": "This action is unauthorized."
     * }
     * @response 404 scenario="Course not found" {
     *   "message": "No query results for model [App\\Models\\Course] 1"
     * }
     * @response 401 scenario="Unauthenticated" {
     *   "message": "Unauthenticated."
     * }
     */
    public function show(Course $course)
    {
        $this->authorize('view', $course);
        $course->load([
            'department:id,name',
            'prerequisites:id,name,code',
        ]);

        return $this->ok(
            'Course retrieved successfully.',
            (new CourseResource($course))
                ->toArray(request()),
        );
    }

    /**
     * Update a Course
     *
     * Updates an existing course. If department is changed the user must be
     * authorized for the new department. Prerequisites are only synced when
     * the key is explicitly included in the request — omitting it leaves
     * existing prerequisites untouched, sending an empty array clears them.
     *
     * @authenticated
     *
     * @urlParam course integer required The ID of the course to update. Example: 91
     *
     * @bodyParam name string optional The name of the course. Example: Database Systems
     * @bodyParam code string optional The course code. Example: CSe322
     * @bodyParam credit_hours integer optional Number of credit hours. Example: 7
     * @bodyParam year_level integer optional The year level this course belongs to. Example: 3
     * @bodyParam is_active boolean optional Whether the course is active. Example: false
     * @bodyParam department_id integer optional The ID of the department. Example: 19
     * @bodyParam prerequisites integer[] optional List of prerequisite course IDs. Replaces existing. Send empty array to clear. Example: [3, 4]
     *
     * @response 200 scenario="Course updated successfully" {
     *   "success": true,
     *   "message": "Course updated successfully.",
     *   "data": {
     *     "id": 91,
     *     "name": "Database Systems",
     *     "code": "CSe322",
     *     "credit_hours": 7,
     *     "semester": "fall"
     *     "year_level": 3,
     *     "is_active": false,
     *     "department_id": 19,
     *     "department": {
     *       "id": 19,
     *       "name": "Information Technology"
     *     },
     *     "prerequisites": [
     *       {
     *         "id": 3,
     *         "name": "Computer Networks",
     *         "code": "SUE41780"
     *       },
     *       {
     *         "id": 4,
     *         "name": "Mobile Application Development",
     *         "code": "KOU74428"
     *       }
     *     ],
     *     "created_at": "2026-07-09 14:33:59",
     *     "updated_at": "2026-07-09 14:45:18"
     *   }
     * }
     * @response 403 scenario="Unauthorized" {
     *   "message": "This action is unauthorized."
     * }
     * @response 404 scenario="Course not found" {
     *   "message": "No query results for model [App\\Models\\Course] 1"
     * }
     * @response 422 scenario="Validation error" {
     *   "message": "The credit hours field must be an integer.",
     *   "errors": {
     *     "credit_hours": ["The credit hours field must be an integer."]
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {
     *   "message": "Unauthenticated."
     * }
     */
    public function update(UpdateCourseRequest $request, Course $course)
    {
        $this->authorize('update', $course);

        $validated = $request->validated();

        if (isset($validated['department_id'])) {
            $department = Department::findOrFail($validated['department_id']);

            $this->authorize('createForDepartment', [Course::class, $department]);
        }
        $course = DB::transaction(function () use ($validated, $course) {
            $hasPrerequisites = array_key_exists('prerequisites', $validated);

            $prerequisites = $validated['prerequisites'] ?? null;

            unset($validated['prerequisites']);

            $course->update($validated);

            if ($hasPrerequisites) {
                $course->prerequisites()->sync($prerequisites ?? []);
            }

            return $course;
        });
        $course->load([
            'department:id,name',
            'prerequisites:id,name,code',
        ]);

        return $this->ok(
            'Course updated successfully.',
            (new CourseResource(
                $course
            ))->toArray($request)
        );
    }

    /**
     * Delete course
     *
     * Delete a course from the system.
     *
     * @group Courses
     *
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Course deleted successfully."
     * }
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     * @response 404 {
     *   "message": "No query results for model [App\\Models\\Course] 1"
     * }
     */
    public function destroy(Course $course)
    {
        $this->authorize('delete', $course);
        $course->delete();

        return $this->ok(
            'Course deleted successfully.'
        );
    }

    /**
     * Get course teachers
     *
     * Retrieves all teachers assigned to the specified course, including
     * their assigned course role and user account information.
     *
     * @group Course Teachers
     *
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Course Teacher retrieved",
     *   "data": [
     *     {
     *       "id": 5,
     *       "user_id": 15,
     *       "pivot": {
     *         "course_id": 1,
     *         "teacher_id": 5,
     *         "role": "primary_lecturer",
     *         "created_at": "2026-07-14T06:36:34.000000Z",
     *         "updated_at": "2026-07-14T06:36:34.000000Z"
     *       },
     *       "user": {
     *         "id": 15,
     *         "name": "Teacher 005",
     *         "email": "teacher005@zankolink.test"
     *       }
     *     },
     *     {
     *       "id": 2,
     *       "user_id": 12,
     *       "pivot": {
     *         "course_id": 1,
     *         "teacher_id": 2,
     *         "role": "assistant_lecturer",
     *         "created_at": "2026-07-14T06:36:34.000000Z",
     *         "updated_at": "2026-07-14T06:36:34.000000Z"
     *       },
     *       "user": {
     *         "id": 12,
     *         "name": "Teacher 002",
     *         "email": "teacher002@zankolink.test"
     *       }
     *     }
     *   ]
     * }
     * @response 404 {
     *   "message": "No query results for model [App\\Models\\Course] 999."
     * }
     */
    public function getTeachers(Course $course)
    {
        $teachers = $course->teachers()
            ->with('user:id,name,email')
            ->get(['teachers.id', 'teachers.user_id']);

        return $this->ok('Course Teacher retrieved', $teachers->toArray());
    }
}
