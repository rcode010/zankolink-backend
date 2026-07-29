<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view students');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Student $student): bool
    {
        return $user->hasRole('MINISTRY_ADMIN')
            || $user->userScopes()
                ->where('scope_type', 'DEPARTMENT')
                ->where('scope_id', $student->department_id)
                ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Department $department): bool
    {
        return $user->hasRole('MINISTRY_ADMIN')
            || $user->userScopes()
                ->where('scope_type', 'DEPARTMENT')
                ->where('scope_id', $department->id)
                ->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Student $student): bool
    {
        return $user->hasRole('MINISTRY_ADMIN')
            || $user->userScopes()
                ->where('scope_type', 'DEPARTMENT')
                ->where('scope_id', $student->department_id)
                ->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Student $student): bool
    {
        return $user->hasRole('MINISTRY_ADMIN');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Student $student): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Student $student): bool
    {
        return false;
    }
}
