<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveStudentSelectionRequest;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateCourseSelectionSettingRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Requests\UpdateDepartmentSeatRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\CourseSelection;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Student;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Department
 *
 * APIs for department CRUD.
 */
class DepartmentController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Department::class);
        $per_page = max(1, min((int) $request->query('per_page', 15), 100));

        $query = Department::query();

        $scope = auth()->user()->userScopes()->first();

        if ($scope) {
            match ($scope->scope_type) {
                'UNIVERSITY' => $query->whereHas(
                    'faculty',
                    fn ($q) => $q->where(
                        'university_id',
                        $scope->scope_id
                    )
                ),

                'FACULTY' => $query->where(
                    'faculty_id',
                    $scope->scope_id
                ),

                default => null,
            };
        }

        $departments = QueryBuilder::for($query)
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::exact('faculty_id'),
                'is_active',
            )
            ->with('faculty:id,name,university_id,is_active', 'admin:id,name')
            ->latest()
            ->paginate($per_page);

        return $this->ok(
            'Departments retrieved successfully.',
            DepartmentResource::collection($departments)
                ->response()
                ->getData(true)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDepartmentRequest $request)
    {
        $this->authorize('create', Department::class);
        $validated = $request->validated();
        $faculty = Faculty::findOrFail($validated['faculty_id']);

        $this->authorize('createForFaculty', [Department::class, $faculty]);

        $department = Department::create(
            $validated
        );

        $department->load('faculty:id,name');

        return $this->success(
            'Department created successfully.',
            (new DepartmentResource($department))->toArray($request),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Department $department)
    {
        $this->authorize('view', $department);
        $department->load('faculty:id,name', 'admin:id,name');

        return $this->ok(
            'Department retrieved successfully.',
            (new DepartmentResource($department))
                ->toArray(request())
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $this->authorize('update', $department);
        $department->update(
            $request->validated()
        );

        return $this->ok(
            'Department updated successfully.',
            (new DepartmentResource(
                $department->fresh()->load('faculty:id,name')
            ))->toArray($request),
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department)
    {
        $this->authorize('delete', $department);
        $department->delete();

        return $this->ok(
            'Department deleted successfully.'
        );
    }

    /**
     * Update department seats.
     */
    public function updateSeat(UpdateDepartmentSeatRequest $request, Department $department)
    {
        $this->authorize('updateSeats', $department);
        $department->update($request->validated());

        return $this->success(
            'Department seats updated successfully.',
            (new DepartmentResource(
                $department->fresh()->load('faculty:id,name')
            ))->toArray($request)
        );
    }

    /**
     * Approve student course selections and enroll them into the final table.
     */
    public function StudentSelection(ApproveStudentSelectionRequest $request)
    {
        $credentials = $request->validated();

        $selection = CourseSelection::query()
            ->with('course:id,name,code,type')
            ->whereKey($credentials['selection_id'])
            ->where('academic_year_id', $credentials['academic_year_id'])
            ->where('status', 'pending')
            ->firstOrFail();

        $student = Student::findOrFail($selection->student_id);
        $department = Department::findOrFail($student->department_id);

        $this->authorize('manageCourseSelections', $department);

        DB::transaction(function () use ($selection, $student, $credentials) {
            $selection->update(['status' => $credentials['status']]);

            if ($credentials['status'] !== 'approved') {
                return;
            }

            DB::table('course_student')->updateOrInsert(
                [
                    'student_id' => $student->id,
                    'course_id' => $selection->course_id,
                    'academic_year_id' => $selection->academic_year_id,
                ],
                [
                    'status' => 'enrolled',
                    'enrolled_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        });

        return $this->ok(
            $credentials['status'] === 'approved'
                ? 'Course selection approved and student enrolled successfully.'
                : 'Course selection rejected successfully.',
            [
                'student_id' => $student->id,
                'academic_year_id' => $credentials['academic_year_id'],
                'status' => $credentials['status'],
                'course' => [
                    'selection_id' => $selection->id,
                    'id' => $selection->course->id,
                    'name' => $selection->course->name,
                    'code' => $selection->course->code,
                    'type' => $selection->course->type,
                ],
            ]
        );
    }
    /**
     * Get the list of pending course selections (for all students or a specific student).
     */
    public function getStudentSelectedCourses(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'sometimes|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $user = $request->user();

        $query = CourseSelection::with(['course', 'student.user:id,name,email'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('status', 'pending');

        if (! empty($validated['student_id'])) {
            $student = Student::findOrFail($validated['student_id']);
            $department = Department::findOrFail($student->department_id);

            $this->authorize('manageCourseSelections', $department);

            $query->where('student_id', $student->id);
        } elseif (! $user->hasRole('MINISTRY_ADMIN')) {
            $scope = $user->userScopes()
                ->where('scope_type', 'DEPARTMENT')
                ->firstOrFail();

            $department = Department::findOrFail($scope->scope_id);

            $this->authorize('manageCourseSelections', $department);

            $query->whereHas('student', function ($q) use ($department) {
                $q->where('department_id', $department->id);
            });
        }

        $selections = $query->latest()->get();

        if ($selections->isEmpty()) {
            return $this->ok('No pending course selections found.', []);
        }

        $selectedCourses = $selections->map(function ($selection) {
            return [
                'id' => $selection->id,
                'student_id' => $selection->student_id,
                'student_name' => $selection->student->user->name ?? 'N/A',
                'student_email' => $selection->student->user->email ?? 'N/A',
                'course_id' => $selection->course->id,
                'course_name' => $selection->course->name,
                'course_code' => $selection->course->code,
                'course_type' => $selection->course->type,
                'status' => $selection->status,
                'selected_at' => $selection->created_at,
            ];
        })->all();

        return $this->ok('Pending courses retrieved successfully.', $selectedCourses);
    }

    /**
     * Update course selection settings
     *
     * Update the course selection start and end date for a department.
     *
     * This endpoint is used by the Head of Department to define when students
     * are allowed to select/enroll in courses for their department.
     *
     * @group Course Selection Settings
     *
     * @authenticated
     *
     * @urlParam department integer required The ID of the department. Example: 1
     *
     * @bodyParam course_selection_starts_at datetime required The date and time when course selection starts. Example: 2026-07-09 21:00:00
     * @bodyParam course_selection_ends_at datetime required The date and time when course selection ends. Must be after course_selection_starts_at. Example: 2026-07-10 21:00:00
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Course selection settings updated successfully.",
     *   "data": {
     *     "department_id": 1,
     *     "course_selection_starts_at": "2026-07-09T21:00:00.000000Z",
     *     "course_selection_ends_at": "2026-07-10T21:00:00.000000Z",
     *     "is_open": false
     *   }
     * }
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     * @response 422 {
     *   "message": "The course selection ends at field must be a date after course selection starts at.",
     *   "errors": {
     *     "course_selection_ends_at": [
     *       "The course selection ends at field must be a date after course selection starts at."
     *     ]
     *   }
     * }
     */
    public function updateCourseSelectionSettings(UpdateCourseSelectionSettingRequest $request, Department $department)
    {
        $this->authorize('updateCourseSelectionSettings', $department);
        $credentials = $request->validated();

        $department->update($credentials);
        $department->refresh();

        $isOpen =
            $department->course_selection_starts_at &&
            $department->course_selection_ends_at &&
            now()->gte($department->course_selection_starts_at) &&
            now()->lt($department->course_selection_ends_at);

        return $this->ok('Course selection settings updated successfully.', [
            'department_id' => $department->id,
            'course_selection_starts_at' => $department->course_selection_starts_at,
            'course_selection_ends_at' => $department->course_selection_ends_at,
            'is_open' => $isOpen,
        ]);
    }

    /**
     * Close course selection
     *
     * Close course selection immediately for a department.
     *
     * This endpoint is used by the Head of Department when they want to stop
     * course selection before the original end date.
     *
     * @group Course Selection Settings
     *
     * @authenticated
     *
     * @urlParam department integer required The ID of the department. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Course selection closed successfully.",
     *   "data": {
     *     "department_id": 1,
     *     "course_selection_starts_at": "2026-07-09T21:00:00.000000Z",
     *     "course_selection_ends_at": "2026-07-12T07:30:00.000000Z",
     *     "is_open": false
     *   }
     * }
     * @response 403 {
     *   "message": "This action is unauthorized."
     * }
     */
    public function closeCourseSelection(Request $request, Department $department)
    {
        $this->authorize('closeCourseSelection', $department);
        $department->update(['course_selection_ends_at' => now()]);
        $department->refresh();

        return $this->ok('Course selection settings updated successfully.', [
            'department_id' => $department->id,
            'course_selection_starts_at' => $department->course_selection_starts_at,
            'course_selection_ends_at' => $department->course_selection_ends_at,
            'is_open' => false,
        ]);
    }
}
