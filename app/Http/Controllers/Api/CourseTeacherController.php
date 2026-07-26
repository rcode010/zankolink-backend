<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignTeacherCourseRequest;
use App\Http\Requests\UpdateTeacherCourseRequest;
use App\Http\Resources\TeacherResource;
use App\Models\Course;
use App\Models\Teacher;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

/**
 * @group Course-Teacher
 *
 * APIs for course-teacher CRUD.
 */
class CourseTeacherController extends Controller
{
    use ApiResponses;

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
        $data = $request->validated();

        $teacher = Teacher::findOrFail($data['teacher_id']);

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
                'role' => $data['role'],
            ],
            ]);

        return $this->ok(
            'Teacher assigned successfully.'
        );
    }

    public function update(UpdateTeacherCourseRequest $request, Course $course, Teacher $teacher)
    {
        $data = $request->validated();

        if (! $course->teachers()->where('teachers.id', $teacher->id)->exists()) {
            return $this->error('Teacher is not assigned to this course.', 404);
        }
        $course->teachers()->updateExistingPivot(
            $teacher->id,
            ['role' => $data['role']]
        );

        return $this->ok(
            'Teacher role updated successfully.'
        );
    }

    public function destroy(Course $course, Teacher $teacher)
    {
        $detached = $course->teachers()
            ->detach($teacher->id);

        if ($detached === 0) {
            return $this->error('Teacher is not assigned to this course.', 404);
        }

        return $this->ok(
            'Teacher removed from course successfully.'
        );
    }
}
