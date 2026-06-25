<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Department;
use App\Models\Teacher;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class CourseTeacherController extends Controller
{
    use ApiResponses;

    public function departmentTeachers(Department $department) {}

    public function courseTeachers(Course $course) {}

    public function store(Request $request, Course $course) {}

    public function update(Request $request, Course $course, Teacher $teacher) {}

    public function destroy(Course $course, Teacher $teacher) {}
}
