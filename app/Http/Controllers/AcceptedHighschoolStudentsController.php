<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\DepartmentOffering;
use App\Models\HighSchoolStudent;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class AcceptedHighschoolStudentsController extends Controller
{
    use ApiResponses;
    public function show(Request $request)
    {
        $user = $request->user();

        $academicYear = AcademicYear::where('is_active', true)->firstOrFail();

        $departmentId = $user->userScopes()
            ->where('scope_type', 'DEPARTMENT')
            ->value('scope_id');

        $departmentOfferingIds = DepartmentOffering::query()
            ->where('department_id', $departmentId)
            ->where('academic_year_id', $academicYear->id)
            ->pluck('id');

        $highschoolStudents = HighSchoolStudent::query()
            ->whereIn('accepted_department_offering_id', $departmentOfferingIds)
            ->get();

        return $this->ok(
            'High school students retrieved successfully.',
            $highschoolStudents->toArray()
        );
    }
}
