<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Http\Resources\TeacherResource;
use App\Models\Department;
use App\Models\Teacher;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Teacher
 *
 * APIs for teacher CRUD.
 */
class TeacherController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Teacher::class);

       $per_page = max(1, min((int) $request->query('per_page', 15), 100));

        $query = Teacher::query();

        $user = $request->user();

        if (! $user->hasRole('MINISTRY_ADMIN')) {
            $departmentId = $user->userScopes()
                ->where('scope_type', 'DEPARTMENT')
                ->value('scope_id');

            if (! $departmentId) {
                return $this->error('You are not assigned to a department.', 403);
            }

            $query->whereHas('departments', function ($q) use ($departmentId) {
                $q->where('departments.id', $departmentId);
            });
        }

        $teachers = QueryBuilder::for($query)
            ->allowedFilters(
                AllowedFilter::callback('search', function ($query, $value) {
                    $query->whereHas('user', function ($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%");
                    });
                }),
            )
            ->with('user:id,name,email', 'departments:id,name')
            ->latest()
            ->paginate($perPage);

        return $this->ok(
            'Teachers retrieved successfully.',
            TeacherResource::collection($teachers)
                ->response()
                ->getData(true)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTeacherRequest $request)
    {
        $this->authorize('create', Teacher::class);
        $validated = $request->validated();

        $department = Department::findOrFail($validated['department_id']);

        $this->authorize('createForDepartment', [Teacher::class, $department]);
        $teacher = Teacher::create(
            $validated
        );

        return $this->success(
            'Teacher created successfully.',
            (new TeacherResource(
                $teacher->load('user')
            ))->toArray($request),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Teacher $teacher)
    {
        $this->authorize('view', $teacher);
        $teacher->load('user:id,name');

        return $this->ok(
            'Teacher retrieved successfully.',
            (new TeacherResource($teacher))
                ->toArray(request())
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTeacherRequest $request, Teacher $teacher)
    {
        $this->authorize('update', $teacher);
        $teacher->update(
            $request->validated()
        );

        return $this->ok(
            'Teacher updated successfully.',
            (new TeacherResource(
                $teacher->fresh()->load('user:id,name')
            ))->toArray($request),
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Teacher $teacher)
    {
        $this->authorize('delete', $teacher);
        $teacher->delete();

        return $this->ok(
            'Teacher deleted successfully.',
        );
    }
}
