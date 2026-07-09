<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\Department;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
/**
 * @group Coruse
 *
 * APIs for Course CRUD.
 */
class CourseController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Course::class);

        $perPage = $request->query('per_page', 15);

        $query = Course::query();

        $user = $request->user();

        if (! $user->hasRole('MINISTRY_ADMIN')) {
            $scope = $user->userScopes()
                ->where('scope_type', 'DEPARTMENT')
                ->firstOrFail();

            $query->where('department_id', $scope->scope_id);
        }

        $courses = QueryBuilder::for($query)
            ->with(['prerequisites','department:id,name'])
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::partial('code'),
                AllowedFilter::exact('department_id'),
                'is_active',
            )
            ->latest()
            ->paginate($perPage);

        return $this->ok(
            'Courses retrieved successfully.',
            CourseResource::collection($courses)
                ->response()
                ->getData(true)
        );
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCourseRequest $request)
    {
        $this->authorize('create', Course::class);
        $validated = $request->validated();
        $department = Department::findOrFail($validated['department_id']);

        $this->authorize('createForDepartment', [Course::class, $department]);
        $course = Course::create(
            $validated
        );

        $course->load('department:id,name');

        return $this->success(
            'Course created successfully.',
            (new CourseResource($course))
                ->toArray($request),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Course $course)
    {
        $this->authorize('view', $course);
        $course->load(
            'department:id,name'
        );

        return $this->ok(
            'Course retrieved successfully.',
            (new CourseResource($course))
                ->toArray(request()),
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCourseRequest $request, Course $course)
    {
        $this->authorize('update', $course);

        $validated = $request->validated();

        if (isset($validated['department_id'])) {
            $department = Department::findOrFail($validated['department_id']);

            $this->authorize('createForDepartment', [Course::class, $department]);
        }

        $course->update($validated);

        return $this->ok(
            'Course updated successfully.',
            (new CourseResource(
                $course->fresh()->load('department:id,name')
            ))->toArray($request)
        );
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course)
    {
        $this->authorize('delete', $course);
        $course->delete();

        return $this->ok(
            'Course deleted successfully.'
        );
    }
}
