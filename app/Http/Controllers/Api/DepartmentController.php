<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Requests\UpdateDepartmentSeatRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\CourseSelection;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Student;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class DepartmentController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Department::class);
        $per_page = $request->query('per_page', 15);

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
            ->with('faculty:id,name', 'admin:id,name')
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
    public function approveStudentSelection(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $department = Department::findOrFail($student->department_id);

        $this->authorize('manageCourseSelections', $department);

        $studentId = $validated['student_id'];
        $academicYearId = $validated['academic_year_id'];

        $pendingSelections = CourseSelection::where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'pending')
            ->get();

        if ($pendingSelections->isEmpty()) {
            return $this->error('No pending course selections found for this student.', 404);
        }

        DB::transaction(function () use ($studentId, $academicYearId, $pendingSelections) {
            CourseSelection::where('student_id', $studentId)
                ->where('academic_year_id', $academicYearId)
                ->where('status', 'pending')
                ->update([
                    'status' => 'approved',
                    'updated_at' => now(),
                ]);

            $enrollmentData = [];

            foreach ($pendingSelections as $selection) {
                $enrollmentData[] = [
                    'student_id' => $studentId,
                    'course_id' => $selection->course_id,
                    'academic_year_id' => $academicYearId,
                    'enrolled_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('course_student')
                ->where('student_id', $studentId)
                ->where('academic_year_id', $academicYearId)
                ->delete();

            DB::table('course_student')->insert($enrollmentData);
        });

        return $this->ok('Department approved successfully and student is now enrolled.');
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

        $query = CourseSelection::with(['course', 'student:id,name,email,department_id'])
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
            return $this->error('No pending course selections found.', 404);
        }

        $selectedCourses = $selections->map(function ($selection) {
            return [
                'id' => $selection->id,
                'student_id' => $selection->student_id,
                'student_name' => $selection->student->name ?? 'N/A',
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
}
