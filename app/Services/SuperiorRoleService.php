<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SuperiorRoleService
{
    public function execute(User $user): Collection
    {
        $currentScope = DB::table('user_scopes')
            ->join('roles', 'roles.id', '=', 'user_scopes.role_id')
            ->where('user_scopes.user_id', $user->id)
            ->where('roles.guard_name', 'web')
            ->whereIn('roles.name', [
                'HEAD_OF_DEPARTMENT',
                'DEAN',

                'UNIVERSITY_ADMIN',
                'UNIVERSITY_ADMIN_ADMINISTRATION',
                'UNIVERSITY_ADMIN_STUDENTS',
                'UNIVERSITY_ADMIN_SCIENCE',

                'MINISTRY_ADMIN',
                'MINISTRY_IMPORT_EXPORT_STAFF',
                'MINISTRY_ADMINISTRATION_HEAD',
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

        return match ($currentScope->role) {
            'HEAD_OF_DEPARTMENT' => $this->deanOfDepartmentFaculty(
                (int) $currentScope->scope_id
            ),

            'DEAN' => $this->universityAdminsOfFacultyUniversity(
                (int) $currentScope->scope_id
            ),

            'UNIVERSITY_ADMIN',
            'UNIVERSITY_ADMIN_ADMINISTRATION',
            'UNIVERSITY_ADMIN_STUDENTS',
            'UNIVERSITY_ADMIN_SCIENCE' => $this->ministryImportExportStaff(),

            'MINISTRY_ADMIN',
            'MINISTRY_IMPORT_EXPORT_STAFF',
            'MINISTRY_ADMINISTRATION_HEAD' => $this->ministryLevelUsers($user->id),

            default => collect(),
        };
    }

    private function deanOfDepartmentFaculty(int $departmentId): Collection
    {
        return DB::table('users')
            ->join('user_scopes', 'users.id', '=', 'user_scopes.user_id')
            ->join('roles', 'roles.id', '=', 'user_scopes.role_id')
            ->where('users.is_active', true)
            ->whereNull('users.deleted_at')
            ->where('roles.name', 'DEAN')
            ->where('roles.guard_name', 'web')
            ->where('user_scopes.scope_type', 'FACULTY')
            ->where('user_scopes.scope_id', function ($query) use ($departmentId) {
                $query->select('faculty_id')
                    ->from('departments')
                    ->where('id', $departmentId)
                    ->limit(1);
            })
            ->select([
                'users.id as user_id',
                'users.name',
                'roles.name as role',
            ])
            ->distinct()
            ->orderBy('users.name')
            ->get();
    }

    private function universityAdminsOfFacultyUniversity(int $facultyId): Collection
    {
        return DB::table('users')
            ->join('user_scopes', 'users.id', '=', 'user_scopes.user_id')
            ->join('roles', 'roles.id', '=', 'user_scopes.role_id')
            ->where('users.is_active', true)
            ->whereNull('users.deleted_at')
            ->whereIn('roles.name', [
                'UNIVERSITY_ADMIN',
                'UNIVERSITY_ADMIN_ADMINISTRATION',
                'UNIVERSITY_ADMIN_STUDENTS',
                'UNIVERSITY_ADMIN_SCIENCE',
            ])
            ->where('roles.guard_name', 'web')
            ->where('user_scopes.scope_type', 'UNIVERSITY')
            ->where('user_scopes.scope_id', function ($query) use ($facultyId) {
                $query->select('university_id')
                    ->from('faculties')
                    ->where('id', $facultyId)
                    ->limit(1);
            })
            ->select([
                'users.id as user_id',
                'users.name',
                'roles.name as role',
            ])
            ->distinct()
            ->orderBy('users.name')
            ->get();
    }

    private function ministryImportExportStaff(): Collection
    {
        return DB::table('users')
            ->join('user_scopes', 'users.id', '=', 'user_scopes.user_id')
            ->join('roles', 'roles.id', '=', 'user_scopes.role_id')
            ->where('users.is_active', true)
            ->whereNull('users.deleted_at')
            ->where('roles.name', 'MINISTRY_IMPORT_EXPORT_STAFF')
            ->where('roles.guard_name', 'web')
            ->where('user_scopes.scope_type', 'MINISTRY')
            ->whereNull('user_scopes.scope_id')
            ->select([
                'users.id as user_id',
                'users.name',
                'roles.name as role',
            ])
            ->distinct()
            ->orderBy('users.name')
            ->get();
    }

    private function ministryLevelUsers(int $currentUserId): Collection
    {
        return DB::table('users')
            ->join('user_scopes', 'users.id', '=', 'user_scopes.user_id')
            ->join('roles', 'roles.id', '=', 'user_scopes.role_id')
            ->where('users.is_active', true)
            ->whereNull('users.deleted_at')
            ->where('users.id', '<>', $currentUserId)
            ->whereIn('roles.name', [
                'MINISTRY_ADMIN',
                'MINISTRY_ADMINISTRATION_HEAD',
                'MINISTRY_IMPORT_EXPORT_STAFF',
            ])
            ->where('roles.guard_name', 'web')
            ->where('user_scopes.scope_type', 'MINISTRY')
            ->whereNull('user_scopes.scope_id')
            ->select([
                'users.id as user_id',
                'users.name',
                'roles.name as role',
            ])
            ->distinct()
            ->orderByRaw("
                CASE roles.name
                    WHEN 'MINISTRY_ADMIN' THEN 1
                    WHEN 'MINISTRY_ADMINISTRATION_HEAD' THEN 2
                    WHEN 'MINISTRY_IMPORT_EXPORT_STAFF' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('users.name')
            ->get();
    }
}
