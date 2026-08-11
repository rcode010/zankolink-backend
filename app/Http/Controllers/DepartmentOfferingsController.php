<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentOfferingRequest;
use App\Http\Requests\UpdateDepartmentOfferingRequest;
use App\Http\Resources\DepartmentOfferingResource;
use App\Models\AcademicYear;
use App\Models\DepartmentOffering;
use App\Services\DepartmentOfferingHierarchyService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class DepartmentOfferingsController extends Controller
{
    use ApiResponses;

    public function index(Request $request, DepartmentOfferingHierarchyService $service)
    {
        $user = $request->user();
        $academic_year = AcademicYear::where('is_active', 1)->firstOrFail();
        $departmentOfferings = DepartmentOffering::query()
            ->where('academic_year_id', $academic_year->id)
            ->when(
                $user->major_type === 'literary',
                fn ($query) => $query->literaryOnly()
            )
            ->with([
                'department.subjects:id,name,credit_number',
                'department:id,name,faculty_id',
                'department.faculty:id,name,university_id',
                'department.faculty.university:id,name',
            ])
            ->get();

        return $this->ok('Department Offerings fetched successfully.', $service->build($departmentOfferings));
    }

    public function upsert(StoreDepartmentOfferingRequest $request)
    {
        $data = $request->validated();

        $academicYearId = AcademicYear::where('is_active', 1)->value('id');
        abort_if($academicYearId === null, 409, 'No active academic year.');

        $now = now();

        $shared = [
            'department_id' => $data['department_id'],
            'academic_year_id' => $academicYearId,
            'governorate' => $data['governorate'],
            'major_type' => $data['major_type'],
            'city' => $data['city'],
            'created_at' => $now,
            'updated_at' => $now,
        ];

        DepartmentOffering::upsert(
            [
                $shared + ['track_type' => 'zankoline', 'capacity' => $data['zankoline_capacity']],
                $shared + ['track_type' => 'parallel',  'capacity' => $data['parallel_capacity']],
            ],
            ['department_id', 'academic_year_id', 'track_type'],
            ['capacity', 'governorate', 'major_type', 'city', 'updated_at'],
        );

        $offerings = DepartmentOffering::query()
            ->where('department_id', $data['department_id'])
            ->where('academic_year_id', $academicYearId)
            ->get();

        return $this->ok(
            'Department offerings saved successfully.',
            DepartmentOfferingResource::collection($offerings)->resolve()
        );
    }

    public function show(Request $request)
    {
        $user = $request->user();
        $academicYear = AcademicYear::where('is_active', 1)->firstOrFail();
        $departmentId = $user->userScopes()
            ->where('scope_type', 'DEPARTMENT')
            ->value('scope_id');

        $offerings = DepartmentOffering::query()
            ->where(['department_id' => $departmentId, 'academic_year_id' => $academicYear->id])
            ->get();

        return $this->ok('Department offerings fetched successfully.', $offerings->toArray());
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
