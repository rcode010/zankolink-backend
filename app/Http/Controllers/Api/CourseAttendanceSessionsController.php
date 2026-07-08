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
     * List attendance sessions
     *
     * Returns a paginated list of attendance sessions belonging to the authenticated teacher.
     *
     * @group Attendance
     *
     * @authenticated
     *
     * @queryParam course_id integer Filter by course ID. Example: 5
     * @queryParam session_date date Filter by session date. Example: 2026-07-08
     * @queryParam per_page integer Number of results per page. Example: 15
     */
    public function index(Request $request)
    {
        $teacher = $request->user()->teacher;

        $request->validate([
            'course_id' => 'nullable|exists:courses,id',
            'session_date' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1',
        ]);

        $per_page = $request->query('per_page', 15);

        $sessions = CourseAttendanceSessions::query()
            ->where('teacher_id', $teacher->id)
            ->when($request->course_id, fn ($query) => $query->where('course_id', $request->course_id)
            )
            ->when($request->session_date, fn ($query) => $query->whereDate('session_date', $request->session_date)
            )
            ->with('course', 'teacher.user', 'academicYear')
            ->latest('session_date')
            ->paginate($per_page);

        return $this->ok(
            'Sessions retrieved successfully.',
            CourseAttendanceSessionResource::collection($sessions)->resolve()
        );

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
     * View attendance session
     *
     * Returns the details of a single attendance session.
     *
     * @group Attendance
     *
     * @authenticated
     *
     * @urlParam session integer required The attendance session ID. Example: 1
     */
    public function show(CourseAttendanceSessions $session, Request $request)
    {
        $teacher = $request->user()->teacher;

        if ($session->teacher_id !== $teacher->id) {
            return $this->error(
                'You are not authorized to view this attendance session.',
                403
            );
        }

        $session->load('course', 'teacher.user', 'academicYear');

        return $this->ok(
            'Attendance session retrieved successfully.',
            (new CourseAttendanceSessionResource($session))->resolve(),
        );
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
