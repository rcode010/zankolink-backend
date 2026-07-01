<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Letter;
use App\Models\Teacher;
use App\Models\University;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;

class LetterActionService
{
    public function execute(Letter $letter): void
    {

        $payload = $this->getPayload($letter);

            match ($letter->type) {
                'hire_teacher' => $this->hireTeacher($payload),
                'fire_teacher' => $this->fireTeacher($payload),
                'create_department' => $this->createDepartment($payload),
                'close_department' => $this->closeDepartment($payload),
                'open_faculty' => $this->openFaculty($payload),
                'close_faculty' => $this->closeFaculty($payload),
                'open_university' => $this->openUniversity($payload),
                'close_university' => $this->closeUniversity($payload),
                default => throw new RuntimeException("Unsupported letter action type: {$letter->type}"),
            };

            $letter->update(['executed_at' => now()]);
    }

    private function hireTeacher(array $payload): void
    {
        $department = Department::findOrFail($this->required($payload, 'department_id'));

        $role = $this->lecturerRole();

        $user = User::create([
            'name' => $this->required($payload, 'name'),
            'email' => $this->required($payload, 'email'),
            'phone' => $payload['phone'] ?? null,
            'password' => Hash::make($payload['password'] ?? 'password'),
        ]);

        $user->assignRole($role);

        UserScope::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'scope_type' => 'DEPARTMENT',
            'scope_id' => $department->id,
        ]);

        $teacher = Teacher::create([
            'user_id' => $user->id,
            'title' => $this->required($payload, 'title'),
            'speciality' => $this->required($payload, 'speciality'),
        ]);

        $department->teachers()->syncWithoutDetaching([
            $teacher->id,
        ]);
    }

    private function fireTeacher(array $payload): void
    {
        $teacher = Teacher::with('user')
            ->findOrFail($this->required($payload, 'teacher_id'));

        $role = $this->lecturerRole();

        if ($teacher->user) {
            UserScope::where('user_id', $teacher->user->id)
                ->where('role_id', $role->id)
                ->delete();

            if ($teacher->user->hasRole($role)) {
                $teacher->user->removeRole($role);
            }
            $teacher->user->update([
                'is_active' => false,
            ]);
        }
        $teacher->departments()->detach();
        $teacher->delete();
    }

    private function createDepartment(array $payload): void
    {
        $faculty = Faculty::findOrFail($this->required($payload, 'faculty_id'));

        Department::create([
            'name' => $this->required($payload, 'name'),
            'faculty_id' => $faculty->id,
            'admin_id' => $payload['admin_id'] ?? null,
            'is_active' => true,
        ]);
    }

    private function closeDepartment(array $payload): void
    {
        $department = Department::findOrFail($this->required($payload, 'department_id'));

        $department->update([
            'is_active' => false,
        ]);
    }

    private function openFaculty(array $payload): void
    {
        $university = University::findOrFail($this->required($payload, 'university_id'));

        Faculty::create([
            'name' => $this->required($payload, 'name'),
            'university_id' => $university->id,
            'admin_id' => $payload['admin_id'] ?? null,
            'is_active' => true,
        ]);
    }

    private function closeFaculty(array $payload): void
    {
        $faculty = Faculty::findOrFail($this->required($payload, 'faculty_id'));

        $faculty->update([
            'is_active' => false,
        ]);
    }

    private function openUniversity(array $payload): void
    {
        $academicYear = AcademicYear::findOrFail($this->required($payload, 'academic_year_id'));

        University::create([
            'name' => $this->required($payload, 'name'),
            'admin_id' => $payload['admin_id'] ?? null,
            'academic_year_id' => $academicYear->id,
            'location' => $this->required($payload, 'location'),
            'start_date' => $this->required($payload, 'start_date'),
            'end_date' => $this->required($payload, 'end_date'),
            'established_year' => $this->required($payload, 'established_year'),
            'is_active' => true,
        ]);
    }

    private function closeUniversity(array $payload): void
    {
        $university = University::findOrFail($this->required($payload, 'university_id'));

        $university->update([
            'is_active' => false,
        ]);
    }

    private function lecturerRole(): Role
    {
        return Role::where('name', 'lecturer')
            ->where('guard_name', 'web')
            ->firstOrFail();
    }

    private function getPayload(Letter $letter): array
    {
        $payload = $letter->payload;

        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }

        if (! is_array($payload)) {
            throw new RuntimeException('Letter payload is missing or invalid.');
        }

        return $payload;
    }

    private function required(array $payload, string $key): mixed
    {
        if (! array_key_exists($key, $payload) || $payload[$key] === null || $payload[$key] === '') {
            throw new RuntimeException("Missing required payload field: {$key}");
        }

        return $payload[$key];
    }
}
