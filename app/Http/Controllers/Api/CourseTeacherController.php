<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignTeacherCourseRequest;
use App\Http\Requests\UpdateTeacherCourseRequest;
use App\Http\Resources\TeacherResource;
use App\Models\Course;
use App\Models\Department;
use App\Models\Teacher;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class CourseTeacherController extends Controller
{
    use ApiResponses;

    public function departmentTeachers(Request $request, Department $department)
    {
        $per_page = $request->query('per_page', 15);

        $teachers = $department->teachers()
            ->with('user:id,name')
            ->paginate($per_page);

        return $this->ok(
            'Department teachers retrieved successfully.',
            TeacherResource::collection($teachers)
                ->response()
                ->getData(true)
        );
    }

    public function courseTeachers(Request $request, Course $course)
    {
        $per_page = $request->query('per_page', 15);

        $teachers = $course->teachers()
            ->with('user:id,name')
            ->paginate($per_page);

        return $this->ok(
            'Course teachers retrieved successfully.',
            TeacherResource::collection($teachers)
                ->response()
                ->getData(true)
        );
    }

    public function store(AssignTeacherCourseRequest $request, Course $course)
    {
        $teacher = Teacher::find(
            $request->validated('teacher_id')
        );

        $belongsToDepartment = $teacher->departments()
            ->where('department_id', $course->department_id)
            ->exists();

        if (! $belongsToDepartment) {
            return $this->error(
                'Teacher does not belong to this department.',
                400
            );
        }

        $course->teachers()
            ->syncWithoutDetaching([$teacher->id => [
                'role' => $request->role,
            ],
            ]);

        return $this->ok(
            'Teacher assigned successfully.'
        );
    }

    public function update(UpdateTeacherCourseRequest $request, Course $course, Teacher $teacher)
    {
        $course->teachers()->updateExistingPivot(
            $teacher->id,
            ['role' => $request->role]
        );

        return $this->ok(
            'Teacher role updated successfully.'
        );
    }

    public function destroy(Course $course, Teacher $teacher) {}
}
