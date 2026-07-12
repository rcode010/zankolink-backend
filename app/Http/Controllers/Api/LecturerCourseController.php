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
     * Returns the full list of courses assigned to the authenticated lecturer.
     *
     * This endpoint is used by the lecturer Moodle dashboard to render the course grid.
     * Each course payload includes the course metadata plus `students_count` and
     * `sections_count`, which are required by the frontend for quickly showing
     * cohort and section totals without extra queries.
     *
     * @authenticated
     * @response 200 {
     *   "message": "Lecturer assigned courses retrieved successfully",
     *   "data": {
     *     "courses": [
     *       {
     *         "id": 1,
     *         "name": "Computer Science 101",
     *         "code": "CS101",
     *         "students_count": 32,
     *         "sections_count": 4
     *       }
     *     ]
     *   }
     * }
     */
    public function courses(Request $request)
    {
        $teacher = auth()->user()->teacher;

        $courses = $teacher->courses()
            ->with(['department:id,name,faculty_id'])
            ->withCount(['students', 'sections'])
            ->latest('courses.created_at')
            ->get();

        return $this->ok(
        'Lecturer assigned courses retrieved successfully',
        CourseResource::collection($courses)->resolve()
    );
    }

    /**
     * GET /api/moodle/lecturer/courses/{course}
     * Returns the complete Moodle course dashboard for a single course.
     *
     * The frontend uses this endpoint to render the lecturer course detail view,
     * including section cards, materials, and assignment summaries. For each
     * section the response includes:
     * - `files_count`, `links_count`, and `notes_count`
     * - normalized `materials` via `SectionItemResource`
     * - `assignments` with per-assignment progress counters
     *
     * @authenticated
     * @response 200 {
     *   "message": "Course dashboard retrieved successfully",
     *   "data": {
     *     "course": {
     *       "id": 1,
     *       "name": "Computer Science 101",
     *       "code": "CS101"
     *     },
     *     "sections": [
     *       {
     *         "id": 7,
     *         "title": "Week 1",
     *         "files_count": 3,
     *         "assignments_count": 2,
     *         "links_count": 1,
     *         "notes_count": 1,
     *         "materials": [],
     *         "assignments": []
     *       }
     *     ]
     *   }
     * }
     */
    public function showCourse(Request $request, Course $course)
    {
        $this->authorize('viewAsLecturer', $course);

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
                $filesCount = $section->items
                    ->reject(fn ($item) => in_array($item->material_file_type, ['link', 'note'], true))
                    ->count();

                $linksCount = $section->items
                    ->filter(fn ($item) => $item->material_file_type === 'link')
                    ->count();

                $notesCount = $section->items
                    ->filter(fn ($item) => $item->material_file_type === 'note')
                    ->count();

                return [
                    'id' => $section->id,
                    'title' => $section->title,
                    'files_count' => $filesCount,
                    'assignments_count' => $section->submissions->count(),
                    'links_count' => $linksCount,
                    'notes_count' => $notesCount,
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
     * Returns a flat summary of all assignments in the course with submission
     * progress counters for the lecturer dashboard.
     *
     * The frontend can use this endpoint as a compact assignment overview without
     * having to manually aggregate the nested section data.
     *
     * @authenticated
     * @response 200 {
     *   "message": "Submissions summary retrieved successfully",
     *   "data": {
     *     "summary": [
     *       {
     *         "id": 10,
     *         "title": "Assignment 1",
     *         "total_submissions_count": 18,
     *         "total_enrolled_students_count": 30,
     *         "submission_progress": "18/30"
     *       }
     *     ]
     *   }
     * }
     */
    public function submissionsSummary(Request $request, Course $course)
    {
        $this->authorize('viewAsLecturer', $course);

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
                'total_submissions_count' => $submittedCount,
                'total_enrolled_students_count' => $totalStudents,
                'submission_progress' => $totalStudents > 0
                    ? sprintf('%d/%d', $submittedCount, $totalStudents)
                    : '0/0',
                'total_students' => $totalStudents,
                'submitted_count' => $submittedCount,
                'not_submitted_count' => max($totalStudents - $submittedCount, 0),
            ]
        );
    }
}
