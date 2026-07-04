<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Department;
use App\Models\User;

class CoursePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('MINISTRY_ADMIN')) {
            return true;
        }

        return $user->userScopes()
            ->where('scope_type', 'DEPARTMENT')
            ->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Course $course): bool
    {
        if ($user->hasRole('MINISTRY_ADMIN')) {
            return true;
        }

        return $user->userScopes()
            ->where('scope_type', 'DEPARTMENT')
            ->where('scope_id', $course->department_id)
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
            ->where('scope_type', 'DEPARTMENT')
            ->exists();
    }
    public function createForDepartment(User $user, Department $department): bool
    {
        if ($user->hasRole('MINISTRY_ADMIN')) {
            return true;
        }

        return $user->userScopes()
            ->where('scope_type', 'DEPARTMENT')
            ->where('scope_id', $department->id)
            ->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Course $course): bool
    {
        if ($user->hasRole('MINISTRY_ADMIN')) {
            return true;
        }

        return $user->userScopes()
            ->where('scope_type', 'DEPARTMENT')
            ->where('scope_id', $course->department_id)
            ->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Course $course): bool
    {
        return $user->hasRole('MINISTRY_ADMIN');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Course $course): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Course $course): bool
    {
        return false;
    }
}
