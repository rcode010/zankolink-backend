<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class DepartmentController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the departments with filters and pagination.
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        // Fetch departments using Spatie QueryBuilder for dynamic filtering
        $departments = QueryBuilder::for(Department::class)
            ->allowedFilters(['name', 'code', 'faculty_id'])
            ->latest()
            ->paginate($request->query('per_page', 15));

        return $this->ok(
            'Departments retrieved successfully.',
            DepartmentResource::collection($departments)->response()->getData(true)
        );
    }

    /**
     * Store a newly created department in storage.
     *
     * @return JsonResponse
     */
    public function store(StoreDepartmentRequest $request)
    {
        // Create department with validated request data
        $department = Department::create($request->validated());

        return $this->success(
            'Department created successfully.',
            new DepartmentResource($department),
            201
        );
    }

    /**
     * Display the specified department details.
     *
     * @return JsonResponse
     */
    public function show(Department $department)
    {
        return $this->ok(
            'Department details retrieved successfully.',
            new DepartmentResource($department)
        );
    }

    /**
     * Update the specified department in storage.
     *
     * @return JsonResponse
     */
    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        // Update the department model instance directly
        $department->update($request->validated());

        return $this->ok(
            'Department updated successfully.',
            new DepartmentResource($department->fresh())
        );
    }

    /**
     * Remove the specified department from storage.
     *
     * @return JsonResponse
     */
    public function destroy(Department $department)
    {
        // Delete the department from database
        $department->delete();

        return $this->ok('Department deleted successfully.');
    }

    /**
     * Get all departments associated with a specific faculty.
     *
     * @param  int  $faculty_id
     * @return JsonResponse
     */
    public function indexByFaculty($faculty_id)
    {
        // Filter departments by faculty_id using QueryBuilder
        $departments = QueryBuilder::for(Department::class)
            ->where('faculty_id', $faculty_id)
            ->get();

        return $this->ok(
            'Faculty departments retrieved successfully.',
            DepartmentResource::collection($departments)
        );
    }
}
