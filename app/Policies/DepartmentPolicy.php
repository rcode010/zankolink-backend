<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\User;

class DepartmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view departments');

    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Department $department): bool
    {
        if ($user->hasRole('MINISTRY_ADMIN')) {
            return true;
        }

        // Head of Department can view their own department
        if ($user->userScopes()
            ->where('scope_type', 'DEPARTMENT')
            ->where('scope_id', $department->id)
            ->exists()
        ) {
            return true;
        }

        // Dean can view departments inside their faculty
        if ($user->userScopes()
            ->where('scope_type', 'FACULTY')
            ->where('scope_id', $department->faculty_id)
            ->exists()
        ) {
            return true;
        }

        $universityId = $department->faculty?->university_id
            ?? $department->faculty()->value('university_id');

        // University admins can view departments inside their university
        return $user->userScopes()
            ->where('scope_type', 'UNIVERSITY')
            ->where('scope_id', $universityId)
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('MINISTRY_ADMIN')) {
            return true;
        }

        return $user->userScopes()
            ->whereIn('scope_type', ['FACULTY', 'UNIVERSITY'])
            ->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Department $department): bool
    {
        if ($user->hasRole('MINISTRY_ADMIN')) {
            return true;
        }

        // Dean can update departments inside their faculty
        if ($user->userScopes()
            ->where('scope_type', 'FACULTY')
            ->where('scope_id', $department->faculty_id)
            ->exists()
        ) {
            return true;
        }

        $universityId = $department->faculty?->university_id
            ?? $department->faculty()->value('university_id');

        // University admins can update departments inside their university
        return $user->userScopes()
            ->where('scope_type', 'UNIVERSITY')
            ->where('scope_id', $universityId)
            ->exists();
    }

    public function updateSeats(User $user, Department $department): bool
    {
        if ($user->hasRole('MINISTRY_ADMIN')) {
            return true;
        }

        return $user->userScopes()
            ->where('scope_type', 'DEPARTMENT')
            ->where('scope_id', $department->id)->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Department $department): bool
    {
        return $user->hasRole('MINISTRY_ADMIN');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Department $department): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Department $department): bool
    {
        return false;
    }

    public function updateCourseSelectionSettings(User $user, Department $department): bool
    {
        return $user->userScopes()
            ->where('scope_type', 'DEPARTMENT')
            ->where('scope_id', $department->id)
            ->exists();
    }

    public function closeCourseSelection(User $user, Department $department): bool
    {
        return $user->userScopes()
            ->where('scope_type', 'DEPARTMENT')
            ->where('scope_id', $department->id)
            ->exists();
    }

    public function createForFaculty(User $user, Faculty $faculty): bool
    {
        if ($user->hasRole('MINISTRY_ADMIN')) {
            return true;
        }

        // Dean can create department inside their own faculty
        if ($user->userScopes()
            ->where('scope_type', 'FACULTY')
            ->where('scope_id', $faculty->id)
            ->exists()
        ) {
            return true;
        }

        // University admin can create department inside faculties of their own university
        return $user->userScopes()
            ->where('scope_type', 'UNIVERSITY')
            ->where('scope_id', $faculty->university_id)
            ->exists();
    }

    public function manageCourseSelections(User $user, Department $department): bool
    {
        if ($user->hasRole('MINISTRY_ADMIN')) {
            return true;
        }

        return $user->userScopes()
            ->where('scope_type', 'DEPARTMENT')
            ->where('scope_id', $department->id)
            ->exists();
    }
}
