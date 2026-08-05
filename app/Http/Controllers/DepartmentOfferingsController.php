<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentOfferingRequest;
use App\Http\Requests\UpdateDepartmentOfferingRequest;
use App\Http\Resources\DepartmentOfferingResource;
use App\Models\AcademicYear;
use App\Models\DepartmentOffering;
use App\Traits\ApiResponses;

class DepartmentOfferingsController extends Controller
{
    use ApiResponses;

    public function index()
    {
        $academic_year = AcademicYear::where('is_active', 1)->firstOrFail();
        $departmentOfferings = DepartmentOffering::query()
            ->with('department.faculty.university')
            ->where('academic_year_id', $academic_year->id)
            ->get();

        return $this->ok('Department Offerings fetched successfully.', (DepartmentOfferingResource::collection($departmentOfferings))->resolve());
    }

    public function store(StoreDepartmentOfferingRequest $request)
    {
        $credentials = $request->validated();

        $departmentOffering = DepartmentOffering::create($credentials);

        return $this->created('Department offering created successfully.', (new DepartmentOfferingResource($departmentOffering))->resolve());
    }

    public function show(DepartmentOffering $departmentOfferings)
    {
        return $departmentOfferings;
    }

    public function update(UpdateDepartmentOfferingRequest $request, DepartmentOffering $departmentOffering)
    {
        $credentials = $request->validated();
        $departmentOffering->update($credentials);
        return $this->ok('Department offerings updated successfully.', (new DepartmentOfferingResource($departmentOffering->fresh()))->resolve());
    }

    public function destroy(DepartmentOffering $departmentOffering)
    {
        $departmentOffering->delete();

        return $this->deleted('Department offering deleted successfully.');
    }
}
