<?php

namespace App\Http\Controllers;

use App\Http\Resources\StudentResource;
use App\Models\Course;
use App\Models\Department;
use App\Models\Student;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class StudentCourseController extends Controller
{
    use ApiResponses;

    public function departmentStudents(Request $request, Department $department)
    {
        $per_page = $request->query('per_page', 15);

        $students = $department->students()
            ->with('user:id,name')
            ->paginate($per_page);

        return $this->ok(
            'Department students retrieved successfully.',
            StudentResource::collection($students)
                ->response()
                ->getData(true)
        );
    }

    public function courseStudents(Request $request, Course $course)
    {
        $per_page = $request->query('per_page', 15);

        $students = $course->students()
            ->with('user:id,name')
            ->paginate($per_page);

        return $this->ok(
            'Course students retrieved successfully.',
            StudentResource::collection($students)
                ->response()
                ->getData(true)
        );
    }

    public function store(Request $request, Course $course)
    {

    }

    public function update(Request $request, Course $course, Student $student)
    {

    }

    public function destroy(Course $course, Student $student)
    {

    }

}
