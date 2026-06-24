<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class StudentController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);

        $student = QueryBuilder::for(Student::class)
            ->with([
                'user:id,name',
                'department:id,name',
            ])
            ->allowedFilters(
                'student_number',
                'stage',
                'status',
                'department.name',
                'user.name',
                'enrollment_type'
            )
            ->Latest()
            ->paginate($perPage);

        return $this->ok(
            'Student retrieved successfully.',
            StudentResource::collection($student)
                ->response()
                ->getData(true)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudentRequest $request)
    {
        $student = Student::create(
            $request->validated()
        );

        return $this->success(
            'Student created successfully.',
            (new StudentResource(
                $student->load([
                    'user:id,name',
                    'department:id,name',
                ])
            ))->toArray($request),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student)
    {
        $student->load([
            'user:id,name',
            'department:id,name',
        ]);

        return $this->ok(
            'Student retrieved successfully.',
            (new StudentResource($student))
                ->toArray(request()),
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStudentRequest $request, Student $student)
    {
        $student->update(
            $request->validated()
        );

        return $this->ok(
            'Student updated successfully.',
            (new StudentResource(
                $student->fresh()->load([
                    'user:id,name',
                    'department:id,name',
                ])
            ))->toArray($request)
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        $student->delete();

        return $this->ok(
            'Student deleted successfully.'
        );
    }
}
