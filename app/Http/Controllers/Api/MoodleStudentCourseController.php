<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

/**
 * Class MoodleStudentCourseController
 * * Manages Moodle-centric academic course delivery, section hierarchies, 
 * learning materials, and contextualized student submission states.
 */
class MoodleStudentCourseController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of all courses the authenticated student is enrolled in.
     *
     * @see \App\Http\Middleware\EnsureUserIsStudent Global profile guard handled at route layer.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function myCourses(Request $request)
    {
        $student = $request->user()->student;

        // Eager load only critical relations exposed by CourseResource to mitigate N+1 anomalies
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
     * Display general metadata for a specific academic course.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Course  $course
     * @return \Illuminate\Http\JsonResponse
     */
    public function showCourse(Request $request, Course $course)
    {
        $student = $request->user()->student;

        // Record-level access control: Enforce specific student enrollment mapping
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
     * Retrieve the hierarchical syllabus structure for a course, containing nested 
     * learning materials, assignments, and localized student submission states.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Course  $course
     * @return \Illuminate\Http\JsonResponse
     */
    public function sections(Request $request, Course $course)
    {
        $student = $request->user()->student;

        if (! $this->studentIsEnrolled($student->id, $course)) {
            return $this->error('You are not enrolled in this course.', 403);
        }

        // Deep-nest relational data while applying constrained eager loading for privacy optimization
        $sections = $course->sections()
            ->with([
                'teacher.user:id,name,email',
                'items', 
                'submissions.attachments', 
                // Scope constraints ensure a student cannot view another peer's submission record
                'submissions.studentSubmissions' => function ($query) use ($student) {
                    $query->where('student_id', $student->id);
                },
            ])
            ->latest()
            ->get()
            ->map(function ($section) {
                // Manual payload composition blocks accidental underlying DB schema exposure (Zero-leak policy)
                return [
                    'id' => $section->id,
                    'title' => $section->title,
                    'teacher' => $section->teacher ? [
                        'id' => $section->teacher->id,
                        'name' => $section->teacher->user?->name,
                        'email' => $section->teacher->user?->email,
                    ] : null, // Gracefully handle unassigned sections without structural payload failure
                    'materials' => $section->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'file_type' => $item->material_file_type,
                            'file_name' => $item->material_file_name,
                            'file_url' => $item->material_file_url,
                        ];
                    }),
                    'assignments' => $section->submissions->map(function ($submission) {
                        // eagerLoaded hasMany relations guarantee a Collection response, protecting against null pointers
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
                            // Contextual computation of data metrics to establish API contract requirements
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
     * Assert relational integrity on the course_student pivot table to authorize access.
     *
     * @param  int  $studentId
     * @param  \App\Models\Course  $course
     * @return bool
     */
    private function studentIsEnrolled(int $studentId, Course $course): bool
    {
        return $course->students()
            ->where('students.id', $studentId)
            ->exists();
    }
}