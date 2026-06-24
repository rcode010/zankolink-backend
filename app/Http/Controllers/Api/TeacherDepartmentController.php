<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignTeacherRequest;
use App\Models\Department;
use App\Models\Teacher;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class TeacherDepartmentController extends Controller
{
    use ApiResponses;
    public function index(Department $department){

    }

    public function store(AssignTeacherRequest $request, Department $department){
        $department->teachers()
            ->syncWithoutDetaching([$request->teacher_id]);

        return $this->ok(
            'Teacher assigned to department',
        );
    }

    public function update(Request $request, Department $department, Teacher $teacher){

    }

    public function destroy(Department $department, Teacher $teacher){

    }

}
