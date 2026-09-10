<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\DepartmentOffering;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class CutoffsController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $student = $request->user();
        $major_type = $student['major_type'];

        $activeYear = AcademicYear::where('is_active', 1)->first();

        $offerings = DepartmentOffering::query()
            ->select(['id', 'department_id', 'academic_year_id', 'track_type', 'minimum_grade', 'major_type'])
            ->with([
                'department:id,name,faculty_id',
                'department.faculty:id,name,university_id',
                'department.faculty.university:id,name',
                'academicYear:id,year',
            ])
            ->where('major_type', $major_type)
            ->whereNotNull('minimum_grade')
            ->get();

        $trends = $offerings
            ->groupBy(fn ($o) => "{$o->department_id}-{$o->track_type}")
            ->map(function ($group) {
                $latestOffering = $group
                    ->sortByDesc(fn ($o) => $o->academicYear->year)
                    ->first();

                return [
                    'department_offering' => $latestOffering,
                    'cutoffs'   => $group
                        ->sortBy(fn ($o) => $o->academicYear->year)
                        ->map(fn ($o) => [
                            'year' => (int) $o->academicYear->year,
                            'min_score' => (float) $o->minimum_grade,
                        ])
                        ->values(),
                ];
            });

        return $this->ok('Cutoff trends retrieved successfully', $trends->toArray());
    }
}
