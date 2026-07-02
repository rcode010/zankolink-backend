<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class SuperiorRoleService
{
    /**
     * Create a new class instance.
     */
    public function execute(User $user)
    {
        $currentRole = $user->roles()->pluck('name')->first();
        $nextLevelRoles = match ($currentRole) {
            'HEAD_OF_DEPARTMENT' => ['DEAN'],
            'DEAN' => ['UNIVERSITY_ADMIN'],
            'UNIVERSITY_ADMIN' => ['MINISTRY_ADMIN'],

            default => [],
        };
        if (empty($nextLevelRoles)) {
            return collect();
        }

        return DB::table('users')
            ->join('model_has_roles', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', User::class);
            })
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->whereIn('roles.name', $nextLevelRoles)
            ->where('roles.guard_name', 'web')
            ->select([
                'users.id as user_id',
                'users.name',
                'roles.name as role',
            ])
            ->orderBy('users.name')
            ->get();
    }
}
