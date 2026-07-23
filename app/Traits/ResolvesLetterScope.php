<?php

namespace App\Traits;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\University;
use App\Models\User;

trait ResolvesLetterScope
{
    /**
     * Verify the authenticated user actually holds the requested scope,
     * and return that UserScope (with its role loaded) or null.
     */
    protected function resolveUserScope(string $scopeType, ?int $scopeId)
    {
        return auth()->user()->userScopes()
            ->where('scope_type', $scopeType)
            ->when($scopeId, fn ($q) => $q->where('scope_id', $scopeId))
            ->with('role')
            ->first();
    }

    /**
     * All official user IDs under a university's hierarchy
     * (university admin + faculty deans + department heads).
     */
    protected function userIdsUnderUniversity(int $universityId)
    {
        $university = University::with('faculties.departments')->findOrFail($universityId);

        $ids = collect([$university->admin_id]);

        foreach ($university->faculties as $faculty) {
            $ids->push($faculty->admin_id);

            foreach ($faculty->departments as $department) {
                $ids->push($department->admin_id);
            }
        }

        return $ids->filter()->unique()->values();
    }

    /**
     * All official user IDs under a faculty's hierarchy
     * (dean + department heads).
     */
    protected function userIdsUnderFaculty(int $facultyId)
    {
        $faculty = Faculty::with('departments')->findOrFail($facultyId);

        $ids = $faculty->departments->pluck('admin_id')->push($faculty->admin_id);

        return $ids->filter()->unique()->values();
    }

    /**
     * All user IDs holding a MINISTRY-scoped role.
     */
    protected function userIdsForMinistry()
    {
        return User::whereHas('userScopes', function ($q) {
            $q->where('scope_type', 'MINISTRY');
        })->pluck('id');
    }

    /**
     * The single official user ID for a department (its head).
     */
    protected function userIdsForDepartment(int $departmentId)
    {
        $department = Department::findOrFail($departmentId);

        return collect([$department->admin_id])->filter()->values();
    }
}
