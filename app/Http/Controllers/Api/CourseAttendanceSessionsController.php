<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseAttendanceSessionsRequest;
use App\Http\Requests\UpdateCourseAttendanceSessionsRequest;
use App\Http\Resources\CourseAttendanceSessionResource;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CourseAttendanceSessions;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

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
     *
     * @response 200 {
     * "success": true,
     * "message": "Sessions retrieved successfully.",
     * "data": [
     * {
     * "id": 9,
     * "title": "Second Lecture",
     * "session_date": "2026-07-17",
     * "start_at": "2026-07-17 04:00:00",
     * "end_at": "2026-07-17 06:00:00",
     * "course": {
     * "id": 1,
     * "name": "Software Architecture",
     * "code": "KOU29129",
     * "credit_hours": 2,
     * "year_level": 3,
     * "is_active": 0,
     * "department_id": 1,
     * "created_at": "2026-07-08 09:06:26",
     * "updated_at": "2026-07-08 09:06:26"
     * },
     * "teacher": {
     * "id": 2,
     * "title": "dr",
     * "speciality": "Networks",
     * "user": {
     * "id": 6,
     * "name": "Prof. Sofia O'Connell"
     * },
     * "created_at": "2026-07-08 09:06:25",
     * "updated_at": "2026-07-08 09:06:25"
     * },
     * "created_at": "2026-07-08T08:44:52.000000Z"
     * },
     * {
     * "id": 1,
     * "title": "First Lecture",
     * "session_date": "2026-07-06",
     * "start_at": "2026-07-06 04:00:00",
     * "end_at": "2026-07-06 06:00:00",
     * "course": {
     * "id": 1,
     * "name": "Software Architecture",
     * "code": "KOU29129",
     * "credit_hours": 2,
     * "year_level": 3,
     * "is_active": 0,
     * "department_id": 1,
     * "created_at": "2026-07-08 09:06:26",
     * "updated_at": "2026-07-08 09:06:26"
     * },
     * "teacher": {
     * "id": 2,
     * "title": "dr",
     * "speciality": "Networks",
     * "user": {
     * "id": 6,
     * "name": "Prof. Sofia O'Connell"
     * },
     * "created_at": "2026-07-08 09:06:25",
     * "updated_at": "2026-07-08 09:06:25"
     * },
     * "created_at": "2026-07-08T07:46:55.000000Z"
     * }
     * ]
     * }
     */
    public function index(Request $request)
    {
        $teacher = $request->user()->teacher;

        $per_page = $request->query('per_page', 15);

        $sessions = QueryBuilder::for(CourseAttendanceSessions::class)
            ->where('teacher_id', $teacher->id)
            ->allowedFilters(
                AllowedFilter::exact('course_id'),
                AllowedFilter::exact('session_date'),
            )
            ->with(['course', 'teacher.user', 'academicYear'])
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
     * @bodyParam start_at datetime required Session start time. Example: 2026-07-08 09:00:00
     * @bodyParam end_at datetime required Session end time. Example: 2026-07-08 10:30:00
     * @bodyParam title string required Session title. Example: Week 5 Lecture
     *
     * @response 201 {
     * "success": true,
     * "message": "Sessions retrieved successfully.",
     * "data": [
     * {
     * "id": 9,
     * "title": "Second Lecture",
     * "session_date": "2026-07-17",
     * "start_at": "2026-07-17 04:00:00",
     * "end_at": "2026-07-17 06:00:00",
     * "course": {
     * "id": 1,
     * "name": "Software Architecture",
     * "code": "KOU29129",
     * "credit_hours": 2,
     * "year_level": 3,
     * "is_active": 0,
     * "department_id": 1,
     * "created_at": "2026-07-08 09:06:26",
     * "updated_at": "2026-07-08 09:06:26"
     * },
     * "teacher": {
     * "id": 2,
     * "title": "dr",
     * "speciality": "Networks",
     * "user": {
     * "id": 6,
     * "name": "Prof. Sofia O'Connell"
     * },
     * "created_at": "2026-07-08 09:06:25",
     * "updated_at": "2026-07-08 09:06:25"
     * },
     * "created_at": "2026-07-08T08:44:52.000000Z"
     * },
     * {
     * "id": 1,
     * "title": "First Lecture",
     * "session_date": "2026-07-06",
     * "start_at": "2026-07-06 04:00:00",
     * "end_at": "2026-07-06 06:00:00",
     * "course": {
     * "id": 1,
     * "name": "Software Architecture",
     * "code": "KOU29129",
     * "credit_hours": 2,
     * "year_level": 3,
     * "is_active": 0,
     * "department_id": 1,
     * "created_at": "2026-07-08 09:06:26",
     * "updated_at": "2026-07-08 09:06:26"
     * },
     * "teacher": {
     * "id": 2,
     * "title": "dr",
     * "speciality": "Networks",
     * "user": {
     * "id": 6,
     * "name": "Prof. Sofia O'Connell"
     * },
     * "created_at": "2026-07-08 09:06:25",
     * "updated_at": "2026-07-08 09:06:25"
     * },
     * "created_at": "2026-07-08T07:46:55.000000Z"
     * }
     * ]
     * }
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
     *
     * @response 200
     * "success": true,
     * "message": "Attendance session retrieved successfully.",
     * "data": {
     * "id": 1,
     * "title": "First Lecture",
     * "session_date": "2026-07-06",
     * "start_at": "2026-07-06 04:00:00",
     * "end_at": "2026-07-06 06:00:00",
     * "course": {
     * "id": 1,
     * "name": "Software Architecture",
     * "code": "KOU29129",
     * "credit_hours": 2,
     * "year_level": 3,
     * "is_active": 0,
     * "department_id": 1,
     * "created_at": "2026-07-08 09:06:26",
     * "updated_at": "2026-07-08 09:06:26"
     * },
     * "teacher": {
     * "id": 2,
     * "title": "dr",
     * "speciality": "Networks",
     * "user": {
     * "id": 6,
     * "name": "Prof. Sofia O'Connell"
     * },
     * "created_at": "2026-07-08 09:06:25",
     * "updated_at": "2026-07-08 09:06:25"
     * },
     * "created_at": "2026-07-08T07:46:55.000000Z"
     * }
     * }
     * @response 403 {
     * "success": false,
     * "message": "You are not authorized to view this attendance session."
     * }
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
     * Update attendance session
     *
     * Updates an existing attendance session.
     *
     * @group Attendance
     *
     * @authenticated
     *
     * @urlParam session integer required The attendance session ID. Example: 1
     *
     * @bodyParam session_date date The session date. Example: 2026-07-08
     * @bodyParam start_at datetime The session start time. Example: 2026-07-08 09:00:00
     * @bodyParam end_at datetime The session end time. Example: 2026-07-08 10:30:00
     * @bodyParam title string The session title. Example: Week 5 Lecture
     *
     * @response 200 {
     * "success": true,
     * "message": "Attendance session updated successfully.",
     * "data": {
     * "id": 10,
     * "title": "Third Lecture",
     * "session_date": "2026-07-18",
     * "start_at": "2026-07-18 16:00:00",
     * "end_at": "2026-07-18 18:00:00",
     * "course": {
     * "id": 1,
     * "name": "Software Architecture",
     * "code": "KOU29129",
     * "credit_hours": 2,
     * "year_level": 3,
     * "is_active": 0,
     * "department_id": 1,
     * "created_at": "2026-07-08 09:06:26",
     * "updated_at": "2026-07-08 09:06:26"
     * },
     * "teacher": {
     * "id": 4,
     * "title": "asst_prof",
     * "speciality": "Software Engineering",
     * "user": {
     * "id": 8,
     * "name": "Connor Schmeler"
     * },
     * "created_at": "2026-07-08 09:06:25",
     * "updated_at": "2026-07-08 09:06:25"
     * },
     * "created_at": "2026-07-08T08:51:12.000000Z"
     * }
     * }
     * @response 403 {
     * "success": false,
     * "message": "You are not authorized to update this attendance session."
     * }
     */
    public function update(UpdateCourseAttendanceSessionsRequest $request, CourseAttendanceSessions $session)
    {
        $teacher = $request->user()->teacher;

        if ($session->teacher_id !== $teacher->id) {
            return $this->error(
                'You are not authorized to update this attendance session.',
                403
            );
        }

        $session->update($request->validated());

        $session->load('course', 'teacher.user', 'academicYear');

        return $this->success(
            'Attendance session updated successfully.',
            (new CourseAttendanceSessionResource($session))->resolve(),
        );
    }

    /**
     * Delete attendance session
     *
     * Deletes an attendance session.
     *
     * @group Attendance
     *
     * @authenticated
     *
     * @urlParam session integer required The attendance session ID. Example: 1
     *
     * @response 200 {
     * "success": true,
     * "message": "Attendance session deleted successfully.",
     * "data": []
     * }
     * @response 403 {
     * "success": false,
     * "message": "You are not authorized to delete this attendance session."
     * }
     */
    public function destroy(CourseAttendanceSessions $session, Request $request)
    {
        $teacher = $request->user()->teacher;

        if ($session->teacher_id !== $teacher->id) {
            return $this->error(
                'You are not authorized to delete this attendance session.',
                403
            );
        }

        $session->delete();

        return $this->success('Attendance session deleted successfully.');
    }
}
