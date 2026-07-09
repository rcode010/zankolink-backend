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
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
/**
 * @group Course-Section
 *
 * APIs for course-section CRUD.
 */
class CourseSectionController extends Controller
{
    use ApiResponses;

    public function index(Request $request, Course $course)
    {
        $per_page = $request->input('per_page', 15);

        $query = $course->sections();

        $sections = QueryBuilder::for($query)
            ->allowedFilters(
                AllowedFilter::partial('title'),
                AllowedFilter::exact('teacher_id'),
            )
            ->with('teacher', 'course')
            ->latest()
            ->paginate($per_page);

        return $this->ok(
            'Course sections retrieved successfully',
            CourseSectionResource::collection($sections)
                ->response()
                ->getData(true)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCourseSectionRequest $request, Course $course)
    {
        $validated = $request->validated();

        $validated['teacher_id'] = auth()->user()->teacher->id;

        $section = $course->sections()->create($validated);

        $section->load('teacher:id,user_id,title', 'course:id,name');

        return $this->success(
            'Course section created successfully.',
            (new CourseSectionResource($section))
                ->toArray($request),
            201
        );
    }

    /**
     * Display the specified section.
     */
    public function show(CourseSection $section)
    {
        $section->load('teacher', 'course');

        return $this->ok(
            'Course section retrieved successfully',
            (new CourseSectionResource($section))->resolve()
        );
    }

    /**
     * Update the specified section in storage.
     */
    public function update(UpdateCourseSectionRequest $request, CourseSection $section)
    {
        $section->update($request->validated());

        return $this->ok(
            'Course section updated successfully.',
            (new CourseSectionResource(
                $section->fresh()->load('teacher', 'course')
            ))->resolve()
        );
    }

    /**
     * Remove the specified section from storage (Soft Delete).
     */
    public function destroy(CourseSection $section)
    {
        $section->delete();

        return $this->ok('Course section deleted successfully.');
    }
}
