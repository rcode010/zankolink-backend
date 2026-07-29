<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Http\Resources\CourseSectionResource;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Student;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Moodle Student Dashboard
 *
 * Endpoints for the student Moodle dashboard. These APIs list the authenticated
 * student's enrolled courses and course sections, including lesson materials,
 * assignment blueprints, submission attachments, and the student's own
 * submission payload with any grade, feedback, and grading timestamp already
 * assigned by the lecturer.
 *
 * --- ACCESS CONTROL ---
 * 1. Resolves the active student profile dynamically from the authenticated session.
 * 2. Enforces course enrollment before returning a student's section view.
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
     */
    public function myCourses(Request $request)
    {
        $student = $this->resolveStudent();
        $academicYear = AcademicYear::where('active', 1)->first();

        $courses = $student->courses()
            ->with(['department', 'teachers.user'])
            ->where('is_active', true)
            ->where('semester',$academicYear->semester)
            ->withCount(['students', 'sections'])
            ->latest()
            ->get();

        return $this->ok(
            'Enrolled courses retrieved successfully.',
            CourseResource::collection($courses)->resolve()
        );
    }

    /**
     * Show My Course Details
     *
     * Show course-level information only for a single enrolled course.
     *
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 1
     */
    public function showCourse(Course $course)
    {
        $this->authorize('viewAsStudent', $course);

        $course->load(['department', 'teachers.user']);

        return $this->ok(
            'Course retrieved successfully.',
            (new CourseResource($course))->resolve()
        );
    }

    /**
     * List My Course Sections
     *
     * Show sections for a single enrolled course, including their materials,
     * assignment blueprints, attachments, and the student's own submission
     * status with any grade, feedback, and submission metadata already
     * available for that assignment.
     *
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 1
     */
    public function sections(Course $course)
    {
        $this->authorize('viewAsStudent', $course);

        $student = $this->resolveStudent();

        $sections = CourseSection::query()
            ->where('course_id', $course->id)
            ->with([
                'teacher.user',
                'course',
                'items',
                'submissions' => fn ($query) => $query->with([
                    'attachments',
                    'studentSubmissions' => fn ($query) => $query
                        ->where('student_id', $student->id)
                        ->with(['student.user', 'submission']),
                ]),
            ])
            ->latest()
            ->get();

        return $this->ok(
            'Course sections retrieved successfully.',
            CourseSectionResource::collection($sections)->resolve()
        );
    }

    /**
     * Resolve the authenticated user's student profile securely.
     */
    private function resolveStudent(): Student
    {
        $student = Auth::user()?->student;

        abort_unless(
            $student,
            403,
            'Only accounts with a student profile can access this resource.'
        );

        return $student;
    }
}
