<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\Teacher;
use App\Models\User;

class TeacherPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view teachers');
    }

    public function view(User $user, Teacher $teacher): bool
    {
        if ($user->hasRole('MINISTRY_ADMIN')) {
            return true;
        }

        // University admin can view teachers assigned to departments inside their university
        if ($user->userScopes()
            ->where('scope_type', 'UNIVERSITY')
            ->whereIn('scope_id', function ($query) use ($teacher) {
                $query->select('faculties.university_id')
                    ->from('departments')
                    ->join('faculties', 'faculties.id', '=', 'departments.faculty_id')
                    ->join('teacher_department', 'teacher_department.department_id', '=', 'departments.id')
                    ->where('teacher_department.teacher_id', $teacher->id);
            })
            ->exists()
        ) {
            return true;
        }

        // Dean can view teachers assigned to departments inside their faculty
        if ($user->userScopes()
            ->where('scope_type', 'FACULTY')
            ->whereIn('scope_id', function ($query) use ($teacher) {
                $query->select('departments.faculty_id')
                    ->from('departments')
                    ->join('teacher_department', 'teacher_department.department_id', '=', 'departments.id')
                    ->where('teacher_department.teacher_id', $teacher->id);
            })
            ->exists()
        ) {
            return true;
        }

        // Head of Department can view teachers assigned to their department
        return $user->userScopes()
            ->where('scope_type', 'DEPARTMENT')
            ->whereIn('scope_id', function ($query) use ($teacher) {
                $query->select('department_id')
                    ->from('teacher_department')
                    ->where('teacher_id', $teacher->id);
            })
            ->exists();
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('MINISTRY_ADMIN')) {
            return true;
        }

        return $user->userScopes()
            ->whereIn('scope_type', ['FACULTY', 'DEPARTMENT'])
            ->exists();
    }

    public function createForDepartment(User $user, Department $department): bool
    {
        if ($user->hasRole('MINISTRY_ADMIN')) {
            return true;
        }

        // Dean can create teachers inside departments of their faculty
        if ($user->userScopes()
            ->where('scope_type', 'FACULTY')
            ->where('scope_id', $department->faculty_id)
            ->exists()
        ) {
            return true;
        }

        // Head of Department can create teachers inside their own department
        return $user->userScopes()
            ->where('scope_type', 'DEPARTMENT')
            ->where('scope_id', $department->id)
            ->exists();
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return $this->view($user, $teacher);
    }

    public function delete(User $user, Teacher $teacher): bool
    {
        return $user->hasRole('MINISTRY_ADMIN');
    }

    public function assign(User $user, Teacher $teacher): bool
    {
        return $this->view($user, $teacher);
    }

    public function restore(User $user, Teacher $teacher): bool
    {
        return false;
    }

    public function forceDelete(User $user, Teacher $teacher): bool
    {
        return false;
    }
}
