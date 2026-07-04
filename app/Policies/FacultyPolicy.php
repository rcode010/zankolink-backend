<?php

namespace App\Policies;

use App\Models\Faculty;
use App\Models\University;
use App\Models\User;

class FacultyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view faculties');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Faculty $faculty): bool
    {
        if ($user->hasRole('MINISTRY_ADMIN')) {
            return true;
        }

        if ($user->userScopes()
            ->where('scope_type', 'FACULTY')
            ->where('scope_id', $faculty->id)->exists()) {
            return true;
        }
        if ($user->userScopes()
            ->where('scope_type', 'UNIVERSITY')
            ->where('scope_id', $faculty->university_id)->exists()) {
            return true;
        }

        return $user->userScopes()
            ->where('scope_type', 'DEPARTMENT')
            ->whereIn('scope_id', function ($query) use ($faculty) {
                $query->select('id')
                    ->from('departments')
                    ->where('faculty_id', $faculty->id);
            })
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
            ->where('scope_type', 'UNIVERSITY')
            ->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Faculty $faculty): bool
    {
        if ($user->hasRole('MINISTRY_ADMIN')) {
            return true;
        }

        return $user->userScopes()
            ->where('scope_type', 'UNIVERSITY')
            ->where('scope_id', $faculty->university_id)->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Faculty $faculty): bool
    {
        return $user->hasRole('MINISTRY_ADMIN');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Faculty $faculty): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Faculty $faculty): bool
    {
        return false;
    }

    public function createForUniversity(User $user, University $university): bool
    {
        if ($user->hasRole('MINISTRY_ADMIN')) {
            return true;
        }

        return $user->userScopes()
            ->where('scope_type', 'UNIVERSITY')
            ->where('scope_id', $university->id)
            ->exists();
    }
}
