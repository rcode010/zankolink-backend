<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentAcceptedGenderRequest;
use App\Models\Department;
use App\Traits\ApiResponses;

class DepartmentAcceptedGenderController extends Controller
{
    use ApiResponses;
    public function index()
    {

    }

    public function update(DepartmentAcceptedGenderRequest $request) {
        $credentials = $request->validated();
        $departmentId = $request->user()->userScopes()
            ->where("scope_type", "DEPARTMENT")
            ->value('scope_id');

        $department = Department::where("id", $departmentId)->firstOrFail();

        $department->update($credentials);

        return $this->ok("Department genders updated successfully!", $department->fresh()->toArray());
    }
}
