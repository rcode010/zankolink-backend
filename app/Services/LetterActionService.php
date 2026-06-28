<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Letter;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LetterActionService
{
    public function __construct() {}

    public function execute(Letter $letter): void
    {
        match ($letter->type) {
            'hire_teacher' => $this->hireTeacher($letter),
            'fire_teacher' => $this->fireTeacher($letter),
            'create_department' => $this->createDepartment($letter),
            'close_department' => $this->closeDepartment($letter),
            'open_faculty' => $this->openFaculty($letter),
            'close_faculty' => $this->closeFaculty($letter),
            'open_university' => $this->openUniversity($letter),
            'close_university' => $this->closeUniversity($letter),
            default => null,
        };
    }

    private function hireTeacher(Letter $letter)
    {
        $payload = $letter->payload;

        DB::transaction(function () use ($payload) {
            $user = User::create([
                'name' => $payload['name'],
                'email' => $payload['email'],
                'phone' => $payload['phone'] ?? null,
                'password' => bcrypt('password'),
            ]);

            $teacher = Teacher::create([
                'user_id' => $user->id,
            ]);

            $department = Department::findOrFail($payload['department_id']);

            $department->teachers()->attach($teacher->id);

            $user->assignRole('lecturer');
        });
    }

    private function fireTeacher(Letter $letter)
    {
        return $letter;

    }

    private function createDepartment(Letter $letter)
    {
        return $letter;

    }

    private function closeDepartment(Letter $letter)
    {
        return $letter;
    }

    private function openFaculty(Letter $letter)
    {
        return $letter;
    }

    private function closeFaculty(Letter $letter)
    {
        return $letter;
    }

    private function openUniversity(Letter $letter) {}

    private function closeUniversity(Letter $letter)
    {
        return $letter;
    }
}
