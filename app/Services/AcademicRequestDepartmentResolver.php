<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicRequestDepartmentResolver
{
    public function resolve(User $user, ?int $departmentId = null): int
    {
        $user->loadMissing(['student', 'teacher']);

        if ($user->student) {
            return $user->student->department_id;
        }

        if ($user->teacher) {
            if (! $departmentId) {
                throw ValidationException::withMessages([
                    'department_id' => ['The department_id field is required for teachers.'],
                ]);
            }

            $exists = DB::table('teacher_department')
                ->where('teacher_id', $user->teacher->id)
                ->where('department_id', $departmentId)
                ->exists();

            if (! $exists) {
                throw ValidationException::withMessages([
                    'department_id' => ['Selected department does not belong to this teacher.'],
                ]);
            }

            return $departmentId;
        }

        throw ValidationException::withMessages([
            'user' => ['Only students or teachers can create academic requests.'],
        ]);
    }
}
