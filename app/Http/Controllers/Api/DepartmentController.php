<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Requests\UpdateDepartmentSeatRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\CourseSelection;
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
        // Validate incoming request. student_id is optional (sometimes) to allow fetching all records.
        $request->validate([
            'student_id' => 'sometimes|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
        ]);

        // Eager load both course and student relations for performance and details
        $query = CourseSelection::with(['course', 'student:id,name,email'])
            ->where('academic_year_id', $request->academic_year_id)
            ->where('status', 'pending');

        // If student_id is provided, filter the query for that specific student
        if ($request->has('student_id') && $request->student_id != '') {
            $query->where('student_id', $request->student_id);
        }

        // Fetch the newest records first
        $selections = $query->latest()->get();

        // Return a 404 response if no pending selections match the criteria
        if ($selections->isEmpty()) {
            return $this->error('No pending course selections found.', 404);
        }

        // Map and format the collection data into a clean structure
        $selectedCourses = $selections->map(function ($selection) {
            return [
                'id' => $selection->id,
                'student_id' => $selection->student_id,
                'student_name' => $selection->student->name ?? 'N/A', // Useful when listing all students
                'course_id' => $selection->course->id,
                'course_name' => $selection->course->name,
                'course_code' => $selection->course->code,
                'course_type' => $selection->course->type,
                'status' => $selection->status,
                'selected_at' => $selection->created_at,
            ];
        })->all(); // Convert the collection to a plain PHP array for the API response trait

        return $this->ok('Pending courses retrieved successfully.', $selectedCourses);
    }
}
