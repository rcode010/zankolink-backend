<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\Student;
use App\Models\Teacher;

class LetterPayloadEnrichmentService
{

    public function run(string $letterType, array $letterPayload): array
    {
        return match ($letterType) {
            'fire_teacher' => $this->snapshotTeacher($letterPayload),
            'close_department' => $this->snapshotDepartment($letterPayload),
            'close_faculty' => $this->snapshotFaculty($letterPayload),
            'remove_student' => $this->snapshotStudent($letterPayload),
            default => collect($letterPayload)->toArray(),
        };
    }

    private function snapshotTeacher(array $payload): array
    {
        $teacher = Teacher::query()
            ->with([
                'user:id,name',
                'departments.faculty',
            ])
            ->findOrFail($payload['teacher_id']);

        $payload['teacher'] = [
            'id' => $teacher->id,
            'name' => $teacher->user->name,
            'title' => $teacher->title,
            'speciality' => $teacher->speciality,
            'departments' => $teacher->departments->map(function ($department) {
                return [
                    'id' => $department->id,
                    'name' => $department->name,
                    'faculty' => [
                        'id' => $department->faculty->id,
                        'name' => $department->faculty->name,
                    ],
                ];
            })->toArray(),
        ];

        return $payload;
    }

    private function snapshotDepartment(array $payload): array
    {
        $department = Department::query()
            ->with([
                'faculty.university',
            ])
            ->findOrFail($payload['department_id']);

        $payload['department'] = [
            'id' => $department->id,
            'name' => $department->name,
            'faculty' => [
                'id' => $department->faculty->id,
                'name' => $department->faculty->name,
            ],
            'university' => [
                'id' => $department->faculty->university->id,
                'name' => $department->faculty->university->name,
            ],
        ];

        return $payload;
    }

    private function snapshotFaculty(array $payload): array
    {
        $faculty = Faculty::query()
            ->with('university')
            ->findOrFail($payload['faculty_id']);

        $payload['faculty'] = [
            'id' => $faculty->id,
            'name' => $faculty->name,
            'university' => [
                'id' => $faculty->university->id,
                'name' => $faculty->university->name,
            ],
        ];

        return $payload;
    }

    private function snapshotStudent(array $payload): array
    {
        $student = Student::query()
            ->with([
                'user:id,name',
                'department.faculty.university',
            ])
            ->findOrFail($payload['student_id']);

        $payload['student'] = [
            'id' => $student->id,
            'name' => $student->user->name ?? $student->name,
            'department' => [
                'id' => $student->department->id,
                'name' => $student->department->name,
            ],
            'faculty' => [
                'id' => $student->department->faculty->id,
                'name' => $student->department->faculty->name,
            ],
            'university' => [
                'id' => $student->department->faculty->university->id,
                'name' => $student->department->faculty->university->name,
            ],
        ];

        return $payload;
    }
}