<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Requests\UpdateDepartmentSeatRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
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
        $per_page = $request->query('per_page', 15);

        $departments = QueryBuilder::for(Department::class)
            ->with('faculty:id,name')
            ->allowedFilters(
                'name',
                'faculty_id',
                'is_active'
            )
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
        $department = Department::create(
            $request->validated()
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
        $department->load('faculty:id,name');

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
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $studentId = $request->student_id;
        $academicYearId = $request->academic_year_id;

        $pendingSelections = DB::table('course_selections')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'pending')
            ->get();

        if ($pendingSelections->isEmpty()) {
            return $this->error('No pending course selections found for this student.', 404);
        }

        DB::transaction(function () use ($studentId, $academicYearId, $pendingSelections) {

            DB::table('course_selections')
                ->where('student_id', $studentId)
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
     * Get the list of courses a specific student has selected and are pending approval.
     */
    public function getStudentSelectedCourses(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $selectedCourses = DB::table('course_selections')
            ->join('courses', 'course_selections.course_id', '=', 'courses.id')
            ->where('course_selections.student_id', $request->student_id)
            ->where('course_selections.academic_year_id', $request->academic_year_id)
            ->where('course_selections.status', 'pending')
            ->select(
                'courses.id as course_id',
                'courses.name as course_name',
                'courses.code as course_code',
                'courses.type as course_type',
                'course_selections.status',
                'course_selections.created_at as selected_at'
            )
            ->get();

        if ($selectedCourses->isEmpty()) {
            return $this->error('No pending course selections found for this student.', 404);
        }

        return $this->ok('Pending courses retrieved successfully.', $selectedCourses);
    }
}