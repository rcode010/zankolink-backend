<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentChoiceRequest;
use App\Models\HighSchoolStudent;
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

    public function show(Request $request,HighSchoolStudent $highSchoolStudent)
    {
        $user = $request->user();
        $choices = $user->choices()
            ->select([
                'id',
                'department_offering_id',
                'preference_order',
                'score',
                'is_local',
            ])
            ->with([
                'department_offering:id,department_id,major_type,track_type',
                'department_offering.department:id,name',
            ])
            ->orderBy('preference_order')
            ->get();

        return $this->ok(
            'Student choices retrieved successfully',
            $choices->toArray()
        );
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
