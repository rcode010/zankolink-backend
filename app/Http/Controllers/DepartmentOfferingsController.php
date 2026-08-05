<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentOfferingRequest;
use App\Http\Resources\DepartmentOfferingResource;
use App\Models\AcademicYear;
use App\Models\DepartmentOffering;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

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

    public function update(Request $request, DepartmentOffering $departmentOfferings)
    {
        $data = $request->validate([

        ]);

        $departmentOfferings->update($data);

        return $departmentOfferings;
    }

    public function destroy(DepartmentOffering $departmentOfferings)
    {
        $departmentOfferings->delete();

        return response()->json();
    }
}
