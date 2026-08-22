<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptedStudentQueryRequest;
use App\Models\Department;
use App\Models\HighSchoolStudent;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AcceptedHighschoolStudentsController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $per_page = max(1, min((int)$request->query('per_page', 15), 100));
        $universityId = $request->user()->userScopes()
            ->where('scope_type', 'UNIVERSITY')
            ->value('scope_id');

        $students = QueryBuilder::for(HighSchoolStudent::class)
            ->whereNotNull('accepted_department_offering_id')
            ->whereHas('acceptedDepartmentOffering.department.faculty.university', function ($query) use ($universityId) {
                $query->where('id', $universityId);
            })
            ->with([
                'contacts',
                'acceptedDepartmentOffering.department.faculty',
            ])
            ->allowedFilters(
                AllowedFilter::exact('accepted_department_offering_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::callback('search', function ($query, $value) {
                    $escaped = str_replace(['%', '_'], ['\%', '\_'], $value);

                    $query->where(function ($q) use ($escaped) {
                        $q->where('name', 'like', "%{$escaped}%")
                            ->orWhere('code', 'like', "%{$escaped}%");
                    });
                })
            )
            ->paginate($per_page);

        return $this->ok(
            'Accepted students retrieved successfully.',
            $students->toArray()
        );
    }

    public function departments(Request $request)
    {
        $universityId = $request->user()->userScopes()
            ->where('scope_type', 'UNIVERSITY')
            ->value('scope_id');

        $departments = Department::withCount('acceptedStudents')
            ->whereHas('faculty.university', function ($query) use ($universityId) {
                $query->where('id', $universityId);
            })
            ->having('accepted_students_count', '>', 0)
            ->get();

        return $this->ok('Departments fetched successfully', $departments->toArray());
    }

    public function statistics(Request $request)
    {
        $universityId = $request->user()->userScopes()
            ->where('scope_type', 'UNIVERSITY')
            ->value('scope_id');

        $accepted = HighSchoolStudent::query()
            ->whereNotNull('accepted_department_offering_id')
            ->whereHas('acceptedDepartmentOffering.department.faculty.university', function ($query) use ($universityId) {
                $query->where('id', $universityId);
            })
            ->count();

        $enrolled = HighSchoolStudent::query()
            ->whereNotNull('accepted_department_offering_id')
            ->whereHas('acceptedDepartmentOffering.department.faculty.university', function ($query) use ($universityId) {
                $query->where('id', $universityId);
            })
            ->whereHas('contacts', function ($q) {
                $q->whereNotNull('email');
            })
            ->count();

        $pending = HighSchoolStudent::query()
            ->whereNotNull('accepted_department_offering_id')
            ->whereHas('acceptedDepartmentOffering.department.faculty.university', function ($query) use ($universityId) {
                $query->where('id', $universityId);
            })
            ->whereHas('contacts', function ($q) {
                $q->whereNull('email');
            })
            ->count();

        return $this->ok('Statistics retrieved successfully.', [
            'accepted' => $accepted,
            'enrolled' => $enrolled,
            'pending' => $pending,
        ]);
    }
}
