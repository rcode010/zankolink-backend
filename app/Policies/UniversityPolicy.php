<?php

namespace App\Policies;

use App\Models\University;
use App\Models\User;

class UniversityPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view universities');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, University $university): bool
    {
        if ($user->hasRole('MINISTRY_ADMIN')) {
            return true;
        }

        // University admin can view their own university
        if ($user->userScopes()
            ->where('scope_type', 'UNIVERSITY')
            ->where('scope_id', $university->id)
            ->exists()
        ) {
            return true;
        }

        // Dean can view the university connected to their faculty
        if ($user->userScopes()
            ->where('scope_type', 'FACULTY')
            ->whereIn('scope_id', function ($query) use ($university) {
                $query->select('id')
                    ->from('faculties')
                    ->where('university_id', $university->id);
            })
            ->exists()
        ) {
            return true;
        }

        // Head of Department can view the university connected to their department
        return $user->userScopes()
            ->where('scope_type', 'DEPARTMENT')
            ->whereIn('scope_id', function ($query) use ($university) {
                $query->select('departments.id')
                    ->from('departments')
                    ->join('faculties', 'faculties.id', '=', 'departments.faculty_id')
                    ->where('faculties.university_id', $university->id);
            })
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('MINISTRY_ADMIN');

    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, University $university): bool
    {
        return $user->hasRole('MINISTRY_ADMIN');

    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, University $university): bool
    {
        return $user->hasRole('MINISTRY_ADMIN');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, University $university): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, University $university): bool
    {
        return false;
    }
}
