<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignStudentCourseRequest;
use App\Http\Requests\UpdateStudentCourseRequest;
use App\Http\Resources\StudentResource;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\Student;
use App\Services\CoursePrerequisiteEligibilityService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
/**
 * @group Student-Course
 *
 * APIs for student-course CRUD.
 */
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

    public function store(AssignStudentCourseRequest $request, Course $course,CoursePrerequisiteEligibilityService $service)
    {
        $student = Student::findOrFail($request->validated('student_id'));

        if ($student->department_id !== $course->department_id) {
            return $this->error(
                'Student cannot enroll outside their department.',
                400
            );
        }
        $activeAcademicYearId = AcademicYear::where('is_active', true)->value('id');
        $eligibility =$service->check($student, $course);
        if (! $eligibility['eligible']) {
            return response()->json([
                'success' => false,
                'message' => 'Student has not passed all prerequisite courses.',
                'missing_prerequisites' => $eligibility['missing_prerequisites'],
            ], 422);
        }


        $course->students()
            ->syncWithoutDetaching([
                $student->id => [
                    'academic_year_id' => $activeAcademicYearId,
                    'enrolled_at' => now(),
                    'status'=>"enrolled"
                ],
            ]);

        return $this->ok(
            'Student assigned successfully.'
        );
    }

    public function update(UpdateStudentCourseRequest $request, Course $course, Student $student)
    {

        if (! $course->students()->where('students.id', $student->id)->exists()) {
            return $this->error('Student is not enrolled in this course.', 404);
        }
        $course->students()->updateExistingPivot(
            $student->id,
            $request->validated()
        );

        return $this->ok(
            'Student course enrollment updated successfully.'
        );
    }

    public function destroy(Course $course, Student $student)
    {
        $detached = $course->students()->detach($student->id);

        // check if pivot row exists before detach
        if ($detached === 0) {
            return $this->error('Student is not enrolled in this course.', 404);
        }

        return $this->ok(
            'Student removed from course successfully.'
        );
    }
}
