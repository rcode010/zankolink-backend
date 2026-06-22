<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{

    // Display a listing of departments with search and filter.
    public function index(Request $request)
    {
        $query = Department::query();

        // Search by name or code
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Filter by faculty_id if provided
        if ($request->has('faculty_id')) {
            $query->where('faculty_id', $request->input('faculty_id'));
        }

        // Paginate results 
        $departments = $query->paginate(10);

        return DepartmentResource::collection($departments);
    
    }

    
    //Store a newly created department in storage.
     
    public function store(StoreDepartmentRequest $request)
    {
        $department = Department::create($request->validated());

        return response()->json([
            'message' => 'Department created successfully',
            'data' => new DepartmentResource($department)
        ], 201); // 201 Created
    }

    //Display the specified department.
    public function show($id)
    {
        $department = Department::findOrFail($id);
        return new DepartmentResource($department);
    }

    //Update the specified department in storage.
    public function update(UpdateDepartmentRequest $request, $id)
    {
        $department = Department::findOrFail($id);
        $department->update($request->validated());

        return response()->json([
            'message' => 'Department updated successfully',
            'data' => new DepartmentResource($department)
        ], 200); // 200 OK
    }

    //Remove the specified department from storage.
    public function destroy($id)
    {
        $department = Department::findOrFail($id);
        $department->delete();

        return response()->json([
            'message' => 'Department deleted successfully'
        ], 200); // 200 OK
    }

    //Get departments belonging to a specific faculty (Nested Route).
    public function indexByFaculty($faculty_id)
    {
        $departments = Department::where('faculty_id', $faculty_id)->get();
        return DepartmentResource::collection($departments);
    }
}
