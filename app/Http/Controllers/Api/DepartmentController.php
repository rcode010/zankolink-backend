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
        // 1. Validate the incoming request from the department panel
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        $studentId = $request->student_id;
        $academicYearId = $request->academic_year_id;

        // 2. Fetch all pending course selections for this student
        $pendingSelections = DB::table('course_selections')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'pending')
            ->get();

        if ($pendingSelections->isEmpty()) {
            return $this->error('No pending course selections found for this student.', 404);
        }

        // 3. Process approval inside a safe database transaction
        DB::transaction(function () use ($studentId, $academicYearId, $pendingSelections) {

            // Step A: Update status to 'approved' in course_selections table
            DB::table('course_selections')
                ->where('student_id', $studentId)
                ->where('academic_year_id', $academicYearId)
                ->where('status', 'pending')
                ->update([
                    'status' => 'approved',
                    'updated_at' => now(),
                ]);

            // Step B: Prepare data for the final enrollment table (course_student)
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

            // Step C: Clear any old records in final table to prevent duplicates, then insert
            DB::table('course_student')
                ->where('student_id', $studentId)
                ->where('academic_year_id', $academicYearId)
                ->delete();

            DB::table('course_student')->insert($enrollmentData);
        });

        return $this->ok('Department approved successfully and student is now enrolled.');
    }
}
