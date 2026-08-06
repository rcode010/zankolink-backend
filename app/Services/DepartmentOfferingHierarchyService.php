<?php

namespace App\Services;

use Illuminate\Support\Collection;

class DepartmentOfferingHierarchyService
{
    /**
     * Create a new class instance.
     */
    public function build(Collection $departmentOfferings): array
    {
        $result = [
            'zankoline' => [],
            'parallel' => [],
        ];

        foreach ($departmentOfferings as $offering) {
            $track = $offering->track_type;

            $governorate = $offering->governorate;

            $university = $offering->department
                ->faculty
                ->university
                ->name;

            $faculty = $offering->department
                ->faculty
                ->name;

            $department = $offering->department
                ->name;

            $result[$track][$governorate][$university][$faculty][$department] = [
                    'id' => $offering->id,
                    'name' => $offering->department->name,
                ];
        }

        return $result;
    }
}
