<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Http\Resources\TeacherResource;
use App\Models\Teacher;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class TeacherController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $teachers = QueryBuilder::for(Teacher::class)
            ->with('user:id,name')
            ->allowedFilters(
                'title',
                'speciality',
                'user.name'
            )
            ->allowedSorts(
                'title',
                'speciality'
            )
            ->latest()
            ->paginate($request->query('per_page', 15));

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
        $teacher = Teacher::create(
            $request->validated()
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
        $teacher->delete();

        return $this->ok(
            'Teacher deleted successfully.',
        );
    }
}
