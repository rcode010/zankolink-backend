<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Department;
use App\Models\Student;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Student
 *
 * APIs for Student CRUD.
 */
class StudentController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Student::class);
        $per_page = $request->query('per_page', 15);

        $user = auth()->user();

        $students = QueryBuilder::for(Student::class)
            ->when(! $user->hasRole('MINISTRY_ADMIN'), function ($query) use ($user) {
                $departmentId = $user->userScopes()
                    ->where('scope_type', 'DEPARTMENT')
                    ->value('scope_id');

                $query->where('department_id', $departmentId);
            })
            ->allowedFilters(
                AllowedFilter::callback(
                    'search',
                    function ($query, $value) {
                        $query->whereHas('user', function ($q) use ($value) {
                            $q->where('name', 'like', "%{$value}%");
                        });
                    }
                ),
                'is_active',
            )
            ->with('user:id,name')
            ->latest()
            ->paginate($per_page);

        return $this->ok(
            'Students retrieved successfully.',
            StudentResource::collection($students)
                ->response()
                ->getData(true)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudentRequest $request)
    {
        $department = Department::findOrFail($request->department_id);
        $this->authorize('create', [Student::class, $department]);

        $student = Student::create(
            $request->validated()
        );

        return $this->success(
            'Student created successfully.',
            (new StudentResource(
                $student->load([
                    'user:id,name,email',
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
        $this->authorize('view', $student);

        $student->load([
            'user:id,name,email',
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
        $this->authorize('update', $student);

        $validated = $request->validated();

        $userData = Arr::only($validated, [
            'name',
            'email',
            'phone',
        ]);

        $studentData = Arr::only($validated, [
            'department_id',
            'enrollment_type',
            'stage',
            'student_number',
            'status',
        ]);

        DB::transaction(function () use ($student, $userData, $studentData) {
            if ($userData !== []) {
                $student->user->update($userData);
            }
            if ($studentData !== []) {
                $student->update($studentData);
            }
        });

        return $this->ok(
            'Student updated successfully.',
            (new StudentResource(
                $student->fresh()->load([
                    'user:id,name,email,phone',
                    'department:id,name',
                ])
            ))->resolve($request)
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        $this->authorize('delete', $student);

        $student->delete();

        return $this->ok(
            'Student deleted successfully.'
        );
    }
}
