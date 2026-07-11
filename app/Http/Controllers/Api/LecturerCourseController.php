<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Http\Resources\SectionItemResource;
use App\Http\Resources\SectionSubmissionResource;
use App\Models\Course;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class LecturerCourseController extends Controller
{
    use ApiResponses;

    /**
     * GET /api/moodle/lecturer/courses
     * List all courses this lecturer is assigned to (via course_teacher).
     */
    public function courses(Request $request)
    {
        $teacher = auth()->user()->teacher;

        $courses = $teacher->courses()
            ->with(['department:id,name,faculty_id'])
            ->latest('courses.created_at')
            ->get();

        return $this->ok('Lecturer assigned courses retrieved successfully', [
            'courses' => CourseResource::collection($courses)->resolve(),
        ]);
    }

    /**
     * GET /api/moodle/lecturer/courses/{course}
     * Full dashboard for one course: sections, materials, assignments,
     * and a submission count per assignment.
     */
    public function showCourse(Request $request, Course $course)
    {
        $teacher = auth()->user()->teacher;

        if (! $this->teacherIsAssigned($teacher->id, $course)) {
            return $this->error('You are not assigned to this course.', 403);
        }

        $totalStudents = $course->students()->count();

        $sections = $course->sections()
            ->with([
                'items',
                'submissions.attachments',
                'submissions.studentSubmissions',
            ])
            ->latest()
            ->get()
            ->map(function ($section) use ($totalStudents) {
                return [
                    'id' => $section->id,
                    'title' => $section->title,
                    'materials' => SectionItemResource::collection($section->items)->resolve(),
                    'assignments' => $section->submissions->map(
                        fn ($submission) => $this->withSubmissionCounts($submission, $totalStudents)
                    ),
                ];
            });

        return $this->ok('Course dashboard retrieved successfully', [
            'course' => (new CourseResource($course->load('department:id,name,faculty_id')))->resolve(),
            'sections' => $sections,
        ]);
    }

    /**
     * GET /api/moodle/lecturer/courses/{course}/submissions-summary
     * Flat list of every assignment in the course with its submission counts.
     */
    public function submissionsSummary(Request $request, Course $course)
    {
        $teacher = auth()->user()->teacher;

        if (! $this->teacherIsAssigned($teacher->id, $course)) {
            return $this->error('You are not assigned to this course.', 403);
        }

        $totalStudents = $course->students()->count();

        $summary = $course->sections()
            ->with('submissions.studentSubmissions')
            ->get()
            ->pluck('submissions')
            ->flatten()
            ->map(fn ($submission) => $this->withSubmissionCounts($submission, $totalStudents))
            ->values();

        return $this->ok('Submissions summary retrieved successfully', [
            'summary' => $summary,
        ]);
    }

    /**
     * Shape one assignment through SectionSubmissionResource (title, description,
     * deadline, attachments), then merge in the submission counts on top —
     * avoids re-declaring the base assignment shape in two places.
     */
    private function withSubmissionCounts($submission, int $totalStudents): array
    {
        $submittedCount = $submission->studentSubmissions
            ->pluck('student_id')
            ->unique()
            ->count();

        return array_merge(
            (new SectionSubmissionResource($submission))->resolve(),
            [
                'total_students' => $totalStudents,
                'submitted_count' => $submittedCount,
                'not_submitted_count' => max($totalStudents - $submittedCount, 0),
            ]
        );
    }

    /**
     * Confirm this teacher is assigned to the course via course_teacher.
     */
    private function teacherIsAssigned(int $teacherId, Course $course): bool
    {
        return $course->teachers()
            ->where('teachers.id', $teacherId)
            ->exists();
    }
}