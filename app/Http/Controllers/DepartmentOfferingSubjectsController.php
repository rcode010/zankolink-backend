<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentOfferingSubjectRequest;
use App\Http\Requests\UpdateDepartmentOfferingSubjectRequest;
use App\Http\Resources\DepartmentOfferingSubjectResource;
use App\Models\DepartmentOfferingSubject;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class DepartmentOfferingSubjectsController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $departmentId = $request->query('department_id');
        $subjects = DepartmentOfferingSubject::query()
            ->with('subject')
            ->where('department_id',$departmentId)->get();
        return $this->ok("Department offering subjects retrieved successfully", $subjects->toArray());
    }

    public function store(StoreDepartmentOfferingSubjectRequest $request)
    {
        $credentials = $request->validated();

        $departmentSubject = DepartmentOfferingSubject::create($credentials);

        return $this->created('Department Offering Subject created', $departmentSubject->toArray());
    }

    public function show(DepartmentOfferingSubject $departmentOfferingSubjects)
    {
        return $departmentOfferingSubjects;
    }

    public function update(UpdateDepartmentOfferingSubjectRequest $request,DepartmentOfferingSubject $departmentOfferingSubject)
    {
        $departmentOfferingSubject->update(
            $request->validated()
        );

        return $this->ok(
            'Department subject updated successfully.',
            (new DepartmentOfferingSubjectResource($departmentOfferingSubject->fresh()))->resolve()
        );
    }

    public function destroy(DepartmentOfferingSubject $departmentOfferingSubject)
    {
        $departmentOfferingSubject->delete();

        return $this->deleted(
            'Department subject deleted successfully.'
        );
    }
}
