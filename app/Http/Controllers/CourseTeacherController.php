<?php

namespace App\Http\Controllers;

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

    public function courseTeachers(Course $course) {}

    public function store(Request $request, Course $course) {}

    public function update(Request $request, Course $course, Teacher $teacher) {}

    public function destroy(Course $course, Teacher $teacher) {}
}
