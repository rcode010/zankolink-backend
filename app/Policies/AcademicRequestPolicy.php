<?php

namespace App\Policies;

use App\Models\User;

class AcademicRequestPolicy
{
    /**
     * Create a new policy instance.
     */
    public function departmentAcademicRequest(User $user): bool
    {
        return $user->hasRole('HEAD_OF_DEPARTMENT');
    }
}
