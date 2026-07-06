<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class MoodleStudentCourseController extends Controller
{
    use ApiResponses;

    /**
     * GET /api/moodle/my-courses
     * List all courses the authenticated student is enrolled in.
     * Note: student-profile check will be handled by route middleware (not written yet).
     */
    public function myCourses(Request $request)
    {
        $student = $request->user()->student;

        // Only load what CourseResource actually exposes (department, teachers)
        $courses = $student->courses()
            ->with([
                'department:id,name,faculty_id',
                'teachers.user:id,name,email',
            ])
            ->latest('courses.created_at')
            ->get();

        return $this->ok('Student enrolled courses retrieved successfully', [
            'courses' => CourseResource::collection($courses)->resolve(),
        ]);
    }

    /**
     * GET /api/moodle/my-courses/{course}
     * Show course-level info only (name, code, department, teachers).
     * Sections/materials/assignments are handled by the sections() endpoint below.
     */
    public function showCourse(Request $request, Course $course)
    {
        $student = $request->user()->student;

        // Per-record check: this student must be enrolled in THIS course
        if (! $this->studentIsEnrolled($student->id, $course)) {
            return $this->error('You are not enrolled in this course.', 403);
        }

        $course->load([
            'department:id,name,faculty_id',
            'teachers.user:id,name,email',
        ]);

        return $this->ok('Course details retrieved successfully', [
            'course' => (new CourseResource($course))->resolve(),
        ]);
    }

    /**
     * GET /api/moodle/my-courses/{course}/sections
     * Show sections for the course, each with its materials, assignments,
     * assignment attachments, and this student's own submission status.
     */
    public function sections(Request $request, Course $course)
    {
        $student = $request->user()->student;

        if (! $this->studentIsEnrolled($student->id, $course)) {
            return $this->error('You are not enrolled in this course.', 403);
        }

        $sections = $course->sections()
            ->with([
                'teacher.user:id,name,email',
                'items', // course materials
                'submissions.attachments', // assignments + their attachments
                // Only load THIS student's own submission per assignment,
                // not every student's — keeps the response small and private
                'submissions.studentSubmissions' => function ($query) use ($student) {
                    $query->where('student_id', $student->id);
                },
            ])
            ->latest()
            ->get()
            ->map(function ($section) {
                // Build the response manually so we only expose the fields
                // the client needs, not the raw model/DB columns
                return [
                    'id' => $section->id,
                    'title' => $section->title,
                    'teacher' => $section->teacher ? [
                        'id' => $section->teacher->id,
                        'name' => $section->teacher->user?->name,
                        'email' => $section->teacher->user?->email,
                    ] : null, // section may have no teacher assigned
                    'materials' => $section->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'file_type' => $item->material_file_type,
                            'file_name' => $item->material_file_name,
                            'file_url' => $item->material_file_url,
                        ];
                    }),
                    'assignments' => $section->submissions->map(function ($submission) {
                        // Already filtered to this student above,
                        // so first() is either their submission or null
                        $studentSubmission = $submission->studentSubmissions->first();

                        return [
                            'id' => $submission->id,
                            'title' => $submission->title,
                            'description' => $submission->description,
                            'deadline' => $submission->deadline,
                            'attachments' => $submission->attachments->map(function ($attachment) {
                                return [
                                    'id' => $attachment->id,
                                    'file_name' => $attachment->file_name,
                                    'file_type' => $attachment->file_type,
                                    'file_size' => $attachment->file_size,
                                    'file_url' => $attachment->file_url,
                                ];
                            }),
                            'submission_status' => $studentSubmission ? 'submitted' : 'not_submitted',
                            'submitted_at' => $studentSubmission?->created_at?->toDateTimeString(),
                        ];
                    }),
                ];
            });

        return $this->ok('Course sections retrieved successfully', [
            'sections' => $sections,
        ]);
    }

    /**
     * Confirm the student has a confirmed enrollment (course_student pivot)
     * in this course before letting them view its details/sections.
     */
    private function studentIsEnrolled(int $studentId, Course $course): bool
    {
        return $course->students()
            ->where('students.id', $studentId)
            ->exists();
    }
}