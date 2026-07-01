<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CourseController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $per_page = $request->query('per_page', 15);

        $query = Course::query();

        $scope = auth()->user()->userScopes()->first();

        if ($scope) {
            match ($scope->scope_type) {

                'UNIVERSITY' => $query->whereHas(
                    'department.faculty',
                    fn ($q) => $q->where(
                        'university_id',
                        $scope->scope_id
                    )
                ),

                'FACULTY' => $query->whereHas(
                    'department',
                    fn ($q) => $q->where(
                        'faculty_id',
                        $scope->scope_id
                    )
                ),

                'DEPARTMENT' => $query->where(
                    'department_id',
                    $scope->scope_id
                ),

                default => null,
            };
        }

        $courses = QueryBuilder::for($query)
            ->with('department:id,name')
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::partial('code'),
                AllowedFilter::exact('department_id'),
                'is_active',
            )
            ->latest()
            ->paginate($per_page);

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
        $course = Course::create(
            $request->validated()
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
        $course->update(
            $request->validated()
        );

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
        $course->delete();

        return $this->ok(
            'Course deleted successfully.'
        );
    }
}
