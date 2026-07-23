<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\University;
use App\Models\User;
use App\Models\UserScope;

class OrganizationalHierarchyService
{
    /**
     * Create a new class instance.
     */
    public function run(User $user)
    {
        return match ($user->getRoleNames()->first()) {
            'DEAN' => $this->getAllDeanDepartments($user),
            'UNIVERSITY_ADMIN' => $this->getAllUniversityFaculties($user),
            'MINISTRY_IMPORT_EXPORT_STAFF' => $this->getAllUniversities(),
            default => collect(),
        };

    }

    private function getAllDeanDepartments(User $user)
    {
        $scope = UserScope::query()->where('user_id', $user->id)->where('scope_type', 'FACULTY')->firstOrFail();

        return Department::select(['admin_id', 'id', 'name'])->where('faculty_id', $scope->scope_id)->with('admin')->get();
    }

    private function getAllUniversityFaculties(User $user)
    {
        $scope = UserScope::query()->where('user_id', $user->id)->where('scope_type', 'UNIVERSITY')->firstOrFail();

        return Faculty::select(['admin_id', 'id', 'name'])->where('university_id', $scope->scope_id)->with('admin')->get();
    }

    private function getAllUniversities()
    {
        return University::select(['admin_id', 'id', 'name'])->with('admin')->get();
    }
}
