<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentChoiceRequest;
use App\Models\StudentsChoice;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class StudentsChoiceController extends Controller
{
    use ApiResponses;

    public function index()
    {
        return StudentsChoice::all();
    }

    public function store(StoreStudentChoiceRequest $request)
    {
        $validated = $request->validated();

        $now = now();
        // ToDo: Validate credits
        $choices = collect($validated['choices'])
            ->map(fn ($choice) => [
                'student_id' => $validated['student_id'],
                'academic_year_id' => $validated['academic_year_id'],
                'department_offering_id' => $choice['department_offering_id'],
                'preference_order' => $choice['preference_order'],
                'score' => $choice['score'],
                'is_local' => $choice['is_local'],
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        StudentsChoice::insert($choices);

        return $this->ok('Student choices stored successfully');
    }

    public function show()
    {
        return null;
    }

    public function update(Request $request, StudentsChoice $studentsChoice)
    {
        $data = $request->validate([

        ]);

        $studentsChoice->update($data);

        return $studentsChoice;
    }

    public function destroy(StudentsChoice $studentsChoice)
    {
        $studentsChoice->delete();

        return response()->json();
    }
}
