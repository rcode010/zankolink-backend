<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseSectionRequest;
use App\Http\Requests\UpdateCourseSectionRequest;
use App\Http\Resources\CourseSectionResource;
use App\Models\Course;
use App\Models\CourseSection;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Course Sections Management
 *
 * Handles CRUD operations for Course Sections.
 * * --- CORE ARCHITECTURE & DESIGN CONTRACT ---
 * 1. StoreCourseSectionRequest Contract:
 * - Validates POST /api/courses/{course}/sections
 * - Expected Body: { "title": "Section A" } (string, required, max:255)
 * - Note: parent course is bound via URL, teacher_id is derived from auth session.
 * * 2. UpdateCourseSectionRequest Contract:
 * - Validates PUT/PATCH /api/sections/{section}
 * - Expected Body (all optional):
 * { "title": "New Title", "course_id": 2, "teacher_id": 5 }
 * - Note: teacher_id is nullable, allowing frontends to unassign teachers.
 * * 3. CourseSectionResource JSON Contract:
 * - Prevents data leaks of internal columns (e.g. deleted_at, raw foreign keys).
 * - Conditionally loads relations using whenLoaded() to prevent N+1 database queries.
 * - Output Shape:
 * {
 * "id": 12,
 * "title": "Section A",
 * "course": { "id": 1, "name": "Laravel Docs" }, // Optional
 * "teacher": { "id": 3, "title": "Dr. John" },   // Optional/Nullable
 * "created_at": "2026-07-08 10:00:00",
 * "updated_at": "2026-07-08 10:30:00"
 * }
 */
class CourseSectionController extends Controller
{
    use ApiResponses;

    /**
     * List Course Sections
     *
     * List all sections belonging to a specific course, with optional filtering and pagination.
     *
     * @authenticated
     *
     * @urlParam course integer required The ID of the parent course. Example: 1
     *
     * @queryParam per_page integer Number of items per page. Default 15, max 100. Example: 15
     * @queryParam filter[title] string Partial match on section title (case-insensitive "LIKE"). Example: Introduction
     * @queryParam filter[teacher_id] integer Exact match on teacher ID. Example: 3
     *
     * @responseFromApiResource App\Http\Resources\CourseSectionResource collection
     */
    public function index(Request $request, Course $course)
    {
        $this->authorize('viewAny', [CourseSection::class, $course]);

      $per_page = max(1, min((int) $request->query('per_page', 15), 100));

        $sections = QueryBuilder::for($course->sections())
            ->addSelect([
                'teacher_role' => DB::table('course_teacher')
                    ->select('role')
                    ->whereColumn('course_teacher.course_id', 'course_sections.course_id')
                    ->whereColumn('course_teacher.teacher_id', 'course_sections.teacher_id')
                    ->limit(1),
            ])
            ->allowedFilters(
                AllowedFilter::partial('title'),
                AllowedFilter::exact('teacher_id'),
            )
            ->with([
                'course',
                'teacher',
                'items',
                'submissions.attachments',
                'submissions.courseAssessment',
            ])
            ->latest()
            ->paginate($per_page);

        return $this->ok(
            'Course sections retrieved successfully',
            CourseSectionResource::collection($sections)->resolve()
        );
    }

    /**
     * Create Course Section
     *
     * Create a new section under the given course. The section is automatically assigned to the authenticated teacher.
     *
     * @authenticated
     *
     * @urlParam course integer required The ID of the parent course. Example: 1
     *
     * @bodyParam title string required The title or name of the new course section. Example: Chapter 1: Setup
     *
     * @responseFromApiResource App\Http\Resources\CourseSectionResource status=201
     *
     * @response status=403 scenario="not a teacher" {
     * "status": "error",
     * "message": "Only accounts with a teacher profile can create course sections.",
     * "data": null
     * }
     */
    public function store(StoreCourseSectionRequest $request, Course $course)
    {
        $this->authorize('create', [CourseSection::class, $course]);

        $validated = $request->validated();
        $teacher = auth()->user()->teacher;

        abort_unless(
            $teacher,
            403,
            'Only accounts with a teacher profile can create course sections.'
        );

        $validated['teacher_id'] = $teacher->id;
        $section = $course->sections()->create($validated);
        $section->load([
            'course:id,name',
            'teacher',
            'items',
            'submissions.creator',
            'submissions.attachments',
            'submissions.courseAssessment',
        ]);
        return $this->success(
            'Course section created successfully.',
            (new CourseSectionResource($section))->resolve(),
            201
        );
    }

    /**
     * Show Section Details
     *
     * Retrieve full details of a single section, including its related teacher and course.
     *
     * @authenticated
     *
     * @urlParam section integer required The ID of the course section. Example: 12
     *
     * @responseFromApiResource App\Http\Resources\CourseSectionResource
     */
    public function show(CourseSection $section)
    {
        $this->authorize('view', $section);

        $section->load('course');

        return $this->ok(
            'Course section retrieved successfully',
            (new CourseSectionResource($section))->resolve()
        );
    }

    /**
     * Update Course Section
     *
     * Update an existing section's details (title, and/or reassign teacher/course).
     *
     * @authenticated
     *
     * @urlParam section integer required The ID of the course section. Example: 12
     *
     * @bodyParam title string The updated title of the section. Example: Chapter 1: Advanced Routing
     * @bodyParam course_id integer The ID of the course this section belongs to. Example: 4
     * @bodyParam teacher_id integer The ID of the teacher assigned to this section. Pass null to unassign. Example: 3
     *
     * @responseFromApiResource App\Http\Resources\CourseSectionResource
     */
    public function update(UpdateCourseSectionRequest $request, CourseSection $section)
    {
        $this->authorize('update', $section);
        $section->update($request->validated());

        return $this->ok(
            'Course section updated successfully.',
            (new CourseSectionResource(
                $section->fresh()->load('course')
            ))->resolve()
        );
    }

    /**
     * Delete Course Section
     *
     * Soft-deletes the section. The record stays in the database with `deleted_at` timestamp set.
     *
     * @authenticated
     *
     * @urlParam section integer required The ID of the course section. Example: 12
     *
     * @response {
     * "status": "success",
     * "message": "Course section deleted successfully.",
     * "data": null
     * }
     */
    public function destroy(CourseSection $section)
    {
        $this->authorize('delete', $section);

        $section->delete();

        return $this->ok('Course section deleted successfully.');
    }
}
