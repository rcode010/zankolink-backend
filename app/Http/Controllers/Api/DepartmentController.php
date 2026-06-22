<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $query = DB::table('departments');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('faculty_id')) {
            $query->where('faculty_id', $request->input('faculty_id'));
        }

        $departments = $query->latest()->paginate(10);
        $resourceCollection = DepartmentResource::collection($departments)->response()->getData(true);

        return $this->ok('Departments retrieved successfully', $resourceCollection);
    }

    public function store(StoreDepartmentRequest $request)
    {
        $data = $request->validated();
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table('departments')->insertGetId($data);
        $department = DB::table('departments')->where('id', $id)->first();
        $resource = new DepartmentResource($department);

        return $this->created('Department created successfully', $resource->resolve());
    }

    public function show($id)
    {
        $department = DB::table('departments')->where('id', $id)->first();

        if (!$department) {
            return $this->error('Department not found', 404);
        }

        $resource = new DepartmentResource($department);
        return $this->ok('Department details retrieved successfully', $resource->resolve());
    }

    public function update(UpdateDepartmentRequest $request, $id)
    {
        $exists = DB::table('departments')->where('id', $id)->exists();

        if (!$exists) {
            return $this->error('Department not found', 404);
        }

        $data = $request->validated();
        $data['updated_at'] = now();

        DB::table('departments')->where('id', $id)->update($data);

        $department = DB::table('departments')->where('id', $id)->first();
        $resource = new DepartmentResource($department);

        return $this->ok('Department updated successfully', $resource->resolve());
    }

    public function destroy($id)
    {
        $deleted = DB::table('departments')->where('id', $id)->delete();

        if (!$deleted) {
            return $this->error('Department not found', 404);
        }

        return $this->deleted('Department deleted successfully');
    }

    public function indexByFaculty($faculty_id)
    {
        $departments = DB::table('departments')->where('faculty_id', $faculty_id)->get();
        $resource = DepartmentResource::collection($departments);

        return $this->ok('Faculty departments retrieved successfully', $resource->resolve());
    }
}