<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Http\Resources\SectionItemResource;
use App\Http\Resources\SectionSubmissionResource;
use App\Models\Course;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

/**
 * @group Moodle Student Dashboard
 *
 * Read-only endpoints for a student viewing their own enrolled courses,
 * course details, and section content (materials + assignments).
 *
 * --- ACCESS CONTROL ---
 * Every method here checks two things before returning data:
 * 1. The authenticated user has a `student` profile at all
 * (guarded here defensively — TODO: move to route middleware once written).
 * 2. For course-scoped endpoints, that student is actually enrolled in
 * the requested course (via the `course_student` pivot), so a student
 * can never view another course's content by guessing its ID.
 *
 * --- RESOURCE USAGE NOTE ---
 * No new Resource classes were added for this feature. Where an existing
 * Resource already covers a shape (CourseResource, SectionItemResource,
 * SectionSubmissionResource), it's reused directly via ->resolve(). Where
 * the student view needs extra fields those Resources don't provide
 * (teacher's flattened name/email, per-student submission status, the
 * student's own submitted file), that logic is written inline below
 * instead of creating another Resource file.
 */
class MoodleStudentCourseController extends Controller
{
    use ApiResponses;

    /**
     * List My Enrolled Courses
     *
     * List all courses the authenticated student is enrolled in.
     *
     * @authenticated
     * @responseFromApiResource App\Http\Resources\CourseResource collection
     */
    public function myCourses(Request $request)
    {
        $student = $this->resolveStudent($request);

        // Only load what CourseResource actually exposes (department, teachers).
        $courses = $student->courses()
        ->withCount(['students', 'sections'])
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
     * Show My Course Details
     *
     * Show course-level info only (name, code, department, teachers).
     * Sections/materials/assignments are handled by the sections() endpoint below.
     *
     * @authenticated
     * @urlParam course integer required The ID of the course. Example: 3
     * @responseFromApiResource App\Http\Resources\CourseResource
     * @response status=403 scenario="not enrolled" {
     * "status": "error",
     * "message": "You are not enrolled in this course.",
     * "data": null
     * }
     */
    public function showCourse(Request $request, Course $course)
    {
        $student = $this->resolveStudent($request);

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
     * List My Course Sections
     *
     * Show sections for the course, each with its materials, assignments,
     * assignment attachments, and this student's own submission status
     * (including their submitted file, if any).
     *
     * @authenticated
     * @urlParam course integer required The ID of the course. Example: 3
     * @response status=403 scenario="not enrolled" {
     * "status": "error",
     * "message": "You are not enrolled in this course.",
     * "data": null
     * }
     */
    public function sections(Request $request, Course $course)
    {
        $student = $this->resolveStudent($request);

        if (! $this->studentIsEnrolled($student->id, $course)) {
            return $this->error('You are not enrolled in this course.', 403);
        }

        $sections = $course->sections()
            ->with([
                'teacher.user:id,name,email',
                'items', // course materials
                'submissions.attachments', // assignments + their attachments
                // Only load THIS student's own submission per assignment,
                // not every student's — keeps the response small and private.
                'submissions.studentSubmissions' => function ($query) use ($student) {
                    $query->where('student_id', $student->id);
                },
            ])
            ->latest()
            ->get()
            ->map(function ($section) {
                return [
                    'id'    => $section->id,
                    'title' => $section->title,

                    // No existing Resource matches this flattened shape
                    // (id/name/email straight from the teacher's user),
                    // so it's written inline rather than adding a new class.
                    'teacher' => $section->teacher ? [
                        'id'    => $section->teacher->id,
                        'name'  => $section->teacher->user?->name,
                        'email' => $section->teacher->user?->email,
                    ] : null,

                    // Reuses the existing SectionItemResource as-is —
                    // it already resolves uploaded-file paths vs. external
                    // URLs via Storage::disk('public')->url().
                    'materials' => SectionItemResource::collection($section->items)->resolve(),

                    'assignments' => $section->submissions->map(function ($submission) {
                        // Relation was pre-scoped to this student in the
                        // query above, so first() is their one submission
                        // (or nothing, if they haven't submitted).
                        $mySubmission = $submission->studentSubmissions->first();

                        // Start from the existing SectionSubmissionResource
                        // output (id, title, description, deadline,
                        // attachments, section, timestamps), then merge in
                        // the two student-specific fields it doesn't know
                        // about — no new Resource class needed for that.
                        return array_merge(
                            (new SectionSubmissionResource($submission))->resolve(),
                            [
                                'submission_status' => $mySubmission ? 'submitted' : 'not_submitted',
                                'my_submission' => $mySubmission ? [
                                    'id'           => $mySubmission->id,
                                    'file_name'    => $mySubmission->file_name,
                                    'file_type'    => $mySubmission->file_type,
                                    'file_size'    => $mySubmission->file_size,
                                    'file_url'     => $mySubmission->file_url,
                                    'submitted_at' => $mySubmission->created_at?->toDateTimeString(),
                                ] : null,
                            ]
                        );
                    }),
                ];
            });

        return $this->ok('Course sections retrieved successfully', [
            'sections' => $sections,
        ]);
    }

    /**
     * Resolve the authenticated user's student profile, or fail with a
     * clear 403 rather than a null-property crash.
     *
     * TODO: remove this guard once a dedicated `EnsureUserIsStudent`
     * route middleware exists — at that point this method can simply
     * return $request->user()->student without the abort_unless check.
     */
    private function resolveStudent(Request $request)
    {
        $student = $request->user()->student;

        abort_unless(
            $student,
            403,
            'Only accounts with a student profile can access this resource.'
        );

        return $student;
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