<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class sameRoleService
{
    public function execute(User $user): Collection
    {
        $currentScope = DB::table('user_scopes')
            ->join('roles', 'roles.id', '=', 'user_scopes.role_id')
            ->where('user_scopes.user_id', $user->id)
            ->where('roles.guard_name', 'web')
            ->whereIn('roles.name', [
                'MINISTRY_ADMIN',
                'MINISTRY_IMPORT_EXPORT_STAFF',
                'MINISTRY_ADMINISTRATION_HEAD',

                'UNIVERSITY_ADMIN',
                'UNIVERSITY_ADMIN_ADMINISTRATION',
                'UNIVERSITY_ADMIN_STUDENTS',
                'UNIVERSITY_ADMIN_SCIENCE',

                'DEAN',
                'HEAD_OF_DEPARTMENT',
            ])
            ->select([
                'roles.name as role',
                'user_scopes.scope_type',
                'user_scopes.scope_id',
            ])
            ->first();

        if (! $currentScope) {
            return collect();
        }

        $sameLevelRoles = $this->sameLevelRoles($currentScope->role);

        if (empty($sameLevelRoles)) {
            return collect();
        }

        return DB::table('users')
            ->join('user_scopes', 'users.id', '=', 'user_scopes.user_id')
            ->join('roles', 'roles.id', '=', 'user_scopes.role_id')
            ->where('users.id', '<>', $user->id)
            ->where('users.is_active', true)
            ->whereNull('users.deleted_at')
            ->where('roles.guard_name', 'web')
            ->whereIn('roles.name', $sameLevelRoles)
            ->where('user_scopes.scope_type', $currentScope->scope_type)
            ->where(function ($query) use ($currentScope) {
                if ($currentScope->scope_id === null) {
                    $query->whereNull('user_scopes.scope_id');
                } else {
                    $query->where('user_scopes.scope_id', $currentScope->scope_id);
                }
            })
            ->select([
                'users.id as user_id',
                'users.name',
                'users.email',
                'roles.id as role_id',
                'roles.name as role',
                'user_scopes.scope_type',
                'user_scopes.scope_id',
            ])
            ->distinct()
            ->orderBy('users.name')
            ->get();
    }

    private function sameLevelRoles(string $role): array
    {
        return match ($role) {
            'HEAD_OF_DEPARTMENT' => [
                'HEAD_OF_DEPARTMENT',
            ],

            'DEAN' => [
                'DEAN',
            ],

            'UNIVERSITY_ADMIN',
            'UNIVERSITY_ADMIN_ADMINISTRATION',
            'UNIVERSITY_ADMIN_STUDENTS',
            'UNIVERSITY_ADMIN_SCIENCE' => [
                'UNIVERSITY_ADMIN',
                'UNIVERSITY_ADMIN_ADMINISTRATION',
                'UNIVERSITY_ADMIN_STUDENTS',
                'UNIVERSITY_ADMIN_SCIENCE',
            ],

            'MINISTRY_ADMIN',
            'MINISTRY_IMPORT_EXPORT_STAFF',
            'MINISTRY_ADMINISTRATION_HEAD' => [
                'MINISTRY_ADMIN',
                'MINISTRY_IMPORT_EXPORT_STAFF',
                'MINISTRY_ADMINISTRATION_HEAD',
            ],

            default => [],
        };
    }
}
