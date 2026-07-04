<?php

namespace App\Services;

use App\Models\Department;
use App\Models\University;
use Illuminate\Support\Facades\DB;

class CreateUniversityStructureService
{
    public function execute(array $data): University
    {
        return DB::transaction(function () use ($data) {
            $university = University::create([
                'name' => $data['name'],
                'admin_id' => $data['admin_id'] ?? null,
                'academic_year_id' => $data['academic_year_id'] ?? null,
                'location' => $data['location'],
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'established_year' => $data['established_year'],
                'is_active' => $data['is_active'],
            ]);

            foreach ($data['faculties'] as $facultyData) {
                $faculty = $university->faculties()->create([
                    'name' => $facultyData['name'],
                    'admin_id' => $facultyData['admin_id'] ?? null,
                    'is_active' => $facultyData['is_active'] ?? true,
                ]);

                $now = now();

                $departments = collect($facultyData['departments'])
                    ->map(fn ($departmentData) => [
                        'faculty_id' => $faculty->id,
                        'name' => $departmentData['name'],
                        'admin_id' => $departmentData['admin_id'] ?? null,
                        'is_active' => $departmentData['is_active'] ?? true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->toArray();

                Department::insert($departments);
            }

            return $university->load('faculties.departments');
        });
    }
}
