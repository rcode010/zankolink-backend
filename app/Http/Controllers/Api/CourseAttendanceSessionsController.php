<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseAttendanceSessionsRequest;
use App\Http\Resources\CourseAttendanceSessionResource;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CourseAttendanceSessions;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class CourseAttendanceSessionsController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Create attendance session
     *
     * Creates a new attendance session for a course.
     *
     * The authenticated teacher is automatically assigned as the session owner,
     * and the currently active academic year is used.
     *
     * @group Attendance
     *
     * @authenticated
     *
     * @bodyParam course_id integer required The course ID. Example: 5
     * @bodyParam session_date date required The session date. Example: 2026-07-08
     * @bodyParam starts_at datetime required Session start time. Example: 2026-07-08 09:00:00
     * @bodyParam ends_at datetime required Session end time. Example: 2026-07-08 10:30:00
     * @bodyParam title string required Session title. Example: Week 5 Lecture
     *
     * @response 201 scenario="Success"
     */
    public function store(StoreCourseAttendanceSessionsRequest $request)
    {
        $data = $request->validated();
        $teacher = $request->user()->teacher;
        $academicYear = AcademicYear::where('is_active', true)->firstOrFail();

        $data['teacher_id'] = $teacher->id;
        $data['academic_year_id'] = $academicYear->id;

        $course = Course::whereKey($data['course_id'])
            ->whereHas('teachers', function ($query) use ($teacher) {
                $query->where('teachers.id', $teacher->id);
            })
            ->first();

        if (! $course) {
            return $this->error('You are not assigned to this course', 403);
        }

        $session = CourseAttendanceSessions::create($data);

        $session->load('course', 'teacher.user', 'academicYear');

        return $this->success(
            'Attendance session created successfully.',
            (new CourseAttendanceSessionResource($session))->resolve(),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
