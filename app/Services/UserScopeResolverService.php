<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserScopeResolverService
{
    public function execute(User $user): Collection
    {
        return DB::table('user_scopes')
            ->leftJoin('roles', 'roles.id', '=', 'user_scopes.role_id')

            ->leftJoin('departments', function ($join) {
                $join->on('departments.id', '=', 'user_scopes.scope_id')
                    ->where('user_scopes.scope_type', '=', 'DEPARTMENT');
            })
            ->leftJoin('faculties as department_faculties', 'department_faculties.id', '=', 'departments.faculty_id')
            ->leftJoin('universities as department_universities', 'department_universities.id', '=', 'department_faculties.university_id')

            ->leftJoin('faculties', function ($join) {
                $join->on('faculties.id', '=', 'user_scopes.scope_id')
                    ->where('user_scopes.scope_type', '=', 'FACULTY');
            })
            ->leftJoin('universities as faculty_universities', 'faculty_universities.id', '=', 'faculties.university_id')

            ->leftJoin('universities', function ($join) {
                $join->on('universities.id', '=', 'user_scopes.scope_id')
                    ->where('user_scopes.scope_type', '=', 'UNIVERSITY');
            })

            ->where('user_scopes.user_id', $user->id)
            ->select([
                'user_scopes.id',
                'user_scopes.scope_type',
                'user_scopes.scope_id',

                'roles.id as role_id',
                'roles.name as role_name',

                'departments.id as department_id',
                'departments.name as department_name',

                'department_faculties.id as department_faculty_id',
                'department_faculties.name as department_faculty_name',

                'department_universities.id as department_university_id',
                'department_universities.name as department_university_name',

                'faculties.id as faculty_id',
                'faculties.name as faculty_name',

                'faculty_universities.id as faculty_university_id',
                'faculty_universities.name as faculty_university_name',

                'universities.id as university_id',
                'universities.name as university_name',
            ])
            ->get()
            ->map(function ($scope) {
                return [
                    'id' => $scope->id,

                    'role' => [
                        'id' => $scope->role_id,
                        'name' => $scope->role_name,
                    ],

                    'scope_type' => $scope->scope_type,
                    'scope_id' => $scope->scope_id,

                    'scope' => $this->formatScope($scope),
                ];
            });
    }

    private function formatScope(object $scope): array
    {
        return match ($scope->scope_type) {
            'MINISTRY' => [
                'id' => null,
                'name' => 'Ministry',
            ],

            'UNIVERSITY' => [
                'id' => $scope->university_id,
                'name' => $scope->university_name,
            ],

            'FACULTY' => [
                'id' => $scope->faculty_id,
                'name' => $scope->faculty_name,
                'university' => [
                    'id' => $scope->faculty_university_id,
                    'name' => $scope->faculty_university_name,
                ],
            ],

            'DEPARTMENT' => [
                'id' => $scope->department_id,
                'name' => $scope->department_name,
                'faculty' => [
                    'id' => $scope->department_faculty_id,
                    'name' => $scope->department_faculty_name,
                    'university' => [
                        'id' => $scope->department_university_id,
                        'name' => $scope->department_university_name,
                    ],
                ],
            ],

            default => [],
        };
    }
}
