<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentAttendanceRequest;
use App\Http\Resources\StudentAttendanceResource;
use App\Models\CourseAttendanceSessions;
use App\Models\StudentAttendance;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class StudentAttendanceController extends Controller
{
    use ApiResponses;

    /**
     * Record attendance
     *
     * Records or updates attendance for students in an attendance session.
     *
     * If a student's attendance already exists for the session, it will be updated.
     *
     * @group Attendance
     *
     * @authenticated
     *
     * @urlParam session integer required The attendance session ID. Example: 1
     *
     * @bodyParam attendance array required List of attendance records.
     * @bodyParam attendance[].student_id integer required The student ID. Example: 5
     * @bodyParam attendance[].status string required Attendance status. Example: present
     * @bodyParam attendance[].note string Optional note. Example: Arrived 15 minutes late
     *
     * @response 200 {
     * "success": true,
     * "message": "Attendance recorded successfully.",
     * "data": []
     * }
     * @response 403 {
     * "success": false,
     * "message": "You are not authorized to record attendance for this session."
     * }
     * @response 422 {
     * "success": false,
     * "message": "Student 1 is not enrolled in this course"
     * }
     */
    public function store(StoreStudentAttendanceRequest $request, CourseAttendanceSessions $session)
    {
        $data = $request->validated();
        $teacher = $request->user()->teacher;

        if ($session->teacher_id != $teacher->id) {
            return $this->error(
                'You are not authorized to record attendance for this session.',
                403
            );
        }

        foreach ($data['attendance'] as $attendance) {
            $studentExists = $session->course
                ->students()
                ->whereKey($attendance['student_id'])
                ->exists();

            if (! $studentExists) {
                return $this->error(
                    "Student {$attendance['student_id']} is not enrolled in this course",
                    422
                );
            }

            StudentAttendance::updateOrCreate(
                [
                    'attendance_session_id' => $session->id,
                    'student_id' => $attendance['student_id'],
                ],
                [
                    'status' => $attendance['status'],
                    'note' => $attendance['note'] ?? null,
                ]
            );
        }

        return $this->success('Attendance recorded successfully.');
    }

    /**
     * View attendance
     *
     * Returns all attendance records for an attendance session.
     *
     * @group Attendance
     *
     * @authenticated
     *
     * @urlParam session integer required The attendance session ID. Example: 1
     *
     * @response 200 {
     * "success": true,
     * "message": "Attendance retrieved successfully.",
     * "data": [
     * {
     * "id": 1,
     * "attendance_session_id": 1,
     * "student": {
     * "id": 20,
     * "enrollment_type": "morning",
     * "stage": 1,
     * "student_number": "ST83956",
     * "status": "active",
     * "user": {
     * "id": 29,
     * "name": "Oswaldo Eichmann"
     * },
     * "created_at": "2026-07-08 14:04:32",
     * "updated_at": "2026-07-08 14:04:32"
     * },
     * "status": "Present",
     * "note": null,
     * "created_at": "2026-07-08T11:07:07.000000Z",
     * "updated_at": "2026-07-08T11:07:07.000000Z"
     * },
     * {
     * "id": 2,
     * "attendance_session_id": 1,
     * "student": {
     * "id": 7,
     * "enrollment_type": "parallel",
     * "stage": 3,
     * "student_number": "ST71824",
     * "status": "active",
     * "user": {
     * "id": 16,
     * "name": "Miss Trinity Rodriguez III"
     * },
     * "created_at": "2026-07-08 14:04:32",
     * "updated_at": "2026-07-08 14:04:32"
     * },
     * "status": "Excused Absence",
     * "note": "Brought doctors note",
     * "created_at": "2026-07-08T11:07:07.000000Z",
     * "updated_at": "2026-07-08T11:09:00.000000Z"
     * }
     * ]
     * }
     * @response 403 {
     *  "success": false,
     *  "message": "You are not authorized to record attendance for this session."
     *  }
     */
    public function getAttendance(CourseAttendanceSessions $session, Request $request)
    {
        $teacher = $request->user()->teacher;

        if ($session->teacher_id != $teacher->id) {
            return $this->error(
                'You are not authorized to record attendance for this session.',
                403
            );
        }

        $session->load('attendance.student.user');

        return $this->ok(
            'Attendance retrieved successfully.',
            StudentAttendanceResource::collection($session->attendance)->resolve()
        );
    }

    /**
     * View my attendance
     *
     * Returns the attendance records for the authenticated student.
     *
     * @group Attendance
     *
     * @authenticated
     *
     * @queryParam course_id integer Filter by course ID. Example: 5
     * @queryParam status string Filter by attendance status. Example: Present
     * @queryParam per_page integer Number of results per page. Example: 15
     *
     * @response 200 {
     * "success": true,
     * "message": "Attendance retrieved successfully.",
     * "data": [
     * {
     * "id": 3,
     * "attendance_session_id": 1,
     * "student": {
     * "id": 7,
     * "enrollment_type": "parallel",
     * "stage": 3,
     * "student_number": "ST71824",
     * "status": "active",
     * "user": {
     * "id": 16,
     * "name": "Miss Trinity Rodriguez III"
     * },
     * "created_at": "2026-07-08 14:04:32",
     * "updated_at": "2026-07-08 14:04:32"
     * },
     * "status": "Excused Absence",
     * "note": "Brought doctors note",
     * "attendance_session": {
     * "id": 1,
     * "title": "Second Lecture",
     * "session_date": "2026-07-17",
     * "start_at": "2026-07-17 04:00:00",
     * "end_at": "2026-07-17 06:00:00",
     * "course": {
     * "id": 2,
     * "name": "Cyber Security",
     * "code": "SUE91153",
     * "credit_hours": 4,
     * "year_level": 1,
     * "is_active": 1,
     * "department_id": 1,
     * "created_at": "2026-07-08 14:04:32",
     * "updated_at": "2026-07-08 14:04:32"
     * },
     * "teacher": {
     * "id": 1,
     * "title": "assoc_prof",
     * "speciality": "Artificial Intelligence",
     * "user": {
     * "id": 5,
     * "name": "Adrian Hilpert"
     * },
     * "created_at": "2026-07-08 14:04:32",
     * "updated_at": "2026-07-08 14:04:32"
     * },
     * "created_at": "2026-07-08T11:06:00.000000Z"
     * },
     * "created_at": "2026-07-08T11:07:07.000000Z",
     * "updated_at": "2026-07-08T11:09:00.000000Z"
     * }
     * ]
     * }
     */
    public function myAttendance(Request $request)
    {
        $student = $request->user()->student;

        $request->validate([
            'course_id' => 'nullable|exists:courses,id',
            'status' => 'nullable|in:Present,Absent,Excused Absence,Late',
            'per_page' => 'nullable|integer|min:1',
        ]);

        $per_page = $request->query('per_page', 15);

        $attendance = StudentAttendance::query()
            ->where('student_id', $student->id)
            ->whereHas('attendanceSession', function ($query) use ($request) {
                $query->when($request->course_id, function ($query) use ($request) {
                    $query->where('course_id', $request->course_id);
                });
            })
            ->when($request->status, function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->with([
                'attendanceSession.course',
                'attendanceSession.teacher.user',
                'student.user',
            ])
            ->latest()
            ->paginate($per_page);

        return $this->ok(
            'Attendance retrieved successfully.',
            StudentAttendanceResource::collection($attendance)->resolve()
        );
    }
}
