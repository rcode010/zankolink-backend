<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignTeacherRequest;
use App\Http\Resources\TeacherResource;
use App\Models\Department;
use App\Models\Teacher;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class TeacherDepartmentController extends Controller
{
    use ApiResponses;
    public function index(Request $request, Department $department)
    {
        $per_page = $request->query('per_page', 15);

        $teachers = $department->teachers()
            ->with('user:id,name')
            ->paginate($per_page);

        return $this->ok(
            'Department teachers retrieved successfully.',
            TeacherResource::collection($teachers)
            ->response()
            ->getData(true)
        );
    }

    public function store(AssignTeacherRequest $request, Department $department)
    {
        $department->teachers()
            ->syncWithoutDetaching([$request->teacher_id]);

        return $this->ok(
            'Teacher assigned to department',
        );
    }

    public function destroy(Department $department, Teacher $teacher)
    {
        $department->teachers()
            ->detach($teacher);

        return $this->ok(
            'Teacher removed from department successfully.'
        );
    }

}
