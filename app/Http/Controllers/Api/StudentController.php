<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    use ApiResponses;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
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
                    'department:id,name'
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreStudentRequest $request, Student $student)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        //
    }
}
