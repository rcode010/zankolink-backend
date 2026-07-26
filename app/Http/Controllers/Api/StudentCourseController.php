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
use Illuminate\Support\Facades\DB;
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
        $per_page = $this->perPage($request);

        $students = $department->students()
            ->with('user:id,name,email')
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
        $per_page = $this->perPage($request);

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
    /**
     * Assign student to course
     *
     * Assign a student to a course for the active academic year.
     * The student must belong to the same department as the course.
     * If the course has prerequisites, the student must have passed all prerequisite courses with status `passed` and grade greater than or equal to 50.
     *
     * @group Student Courses
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 1
     *
     * @bodyParam student_id integer required The ID of the student to assign to the course. Example: 5
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Student assigned successfully.",
     *   "data": []
     * }
     *
     * @response 400 {
     *   "success": false,
     *   "message": "Student cannot enroll outside their department."
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "Student has not passed all prerequisite courses.",
     *   "missing_prerequisites": [2, 4]
     * }
     */
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
        $eligibility = $service->check($student, $course);
        if (! $eligibility['eligible']) {
            return response()->json([
                'success' => false,
                'message' => 'Student has not passed all prerequisite courses.',
                'missing_prerequisites' => $eligibility['missing_prerequisites'],
            ], 422);
        }

        // course_student is uniquely identified by
        // (course_id, student_id, academic_year_id).
        // Do not use belongsToMany pivot helpers here because they
        // identify rows only by (course_id, student_id), which can
        // overwrite historical academic-year enrollments.
        $alreadyEnrolled = DB::table('course_student')
            ->where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->where('academic_year_id', $activeAcademicYearId)
            ->exists();

        if ($alreadyEnrolled) {
            return $this->error(
                'Student is already enrolled in this course for the current academic year.',
                409
            );
        }

        DB::table('course_student')->insert([
            'course_id' => $course->id,
            'student_id' => $student->id,
            'academic_year_id' => $activeAcademicYearId,
            'status' => 'enrolled',
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->ok(
            'Student assigned successfully.'
        );
    }

    /**
     * Update a student's course enrollment
     *
     * Updates the enrollment row for the given academic year — defaults to the
     * currently active academic year if none is specified.
     *
     * @group Student Courses
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 1
     * @urlParam student integer required The ID of the student. Example: 5
     *
     * @bodyParam status string optional One of enrolled, passed, failed, withdrawn. Example: passed
     * @bodyParam grade number optional The student's grade for this enrollment. Example: 87.5
     * @bodyParam enrolled_at string optional Enrollment date. Example: 2026-09-01
     * @bodyParam academic_year_id integer optional The academic year of the enrollment to update. Defaults to the active academic year. Example: 3
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Student course enrollment updated successfully."
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Student is not enrolled in this course for that academic year."
     * }
     */
    public function update(UpdateStudentCourseRequest $request, Course $course, Student $student)
    {
        $validated = $request->validated();

        // Target the same academic year the enrollment applies to: whatever the caller
        // specified, falling back to the currently active year. Never resolve this by
        // grabbing "any" row for the student+course pair — that's the ambiguity that
        // caused the original overwrite bug.
        $academicYearId = $validated['academic_year_id']
            ?? AcademicYear::query()->where('is_active', true)->value('id');
        unset($validated['academic_year_id']);
        $validated['updated_at'] = now();

        $updated = DB::table('course_student')
            ->where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYearId)
            ->update($validated);

        if (! $updated) {
            return $this->error(
                'Student is not enrolled in this course for that academic year.',
                404
            );
        }

        return $this->ok(
            'Student course enrollment updated successfully.'
        );
    }

    /**
     * Remove a student from a course
     *
     * Deletes the enrollment row for the given academic year — defaults to the
     * currently active academic year if none is specified. Does not affect the
     * student's enrollment in this course for other academic years.
     *
     * @group Student Courses
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 1
     * @urlParam student integer required The ID of the student. Example: 5
     *
     * @queryParam academic_year_id integer optional The academic year of the enrollment to delete. Defaults to the active academic year. Example: 3
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Student removed from course successfully."
     * }
     *
     * @response 404 {
     *   "success": false,
     *   "message": "Student is not enrolled in this course for that academic year."
     * }
     *
     * @response 422 {
     *   "success": false,
     *   "message": "The selected academic year id is invalid."
     * }
     */
    public function destroy(Request $request, Course $course, Student $student)
    {
        $request->validate([
            'academic_year_id' => 'sometimes|integer|exists:academic_years,id',
        ]);

        $academicYearId = $request->query('academic_year_id')
            ?? AcademicYear::query()->where('is_active', true)->value('id');

        $deleted = DB::table('course_student')
            ->where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYearId)
            ->delete();

        if (! $deleted) {
            return $this->error(
                'Student is not enrolled in this course for that academic year.',
                404
            );
        }

        return $this->ok(
            'Student removed from course successfully.'
        );
    }
}