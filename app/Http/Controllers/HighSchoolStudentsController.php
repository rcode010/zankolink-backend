<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponses;

use App\Models\HighSchoolStudent;
use Illuminate\Http\Request;

class HighSchoolStudentsController extends Controller
{

    use ApiResponses;
    public function index()
    {
        return HighSchoolStudent::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([

        ]);

        return HighSchoolStudent::create($data);
    }

    public function show(HighSchoolStudent $highSchoolStudents)
    {
        return $highSchoolStudents;
    }

    public function update(Request $request, HighSchoolStudent $highSchoolStudents)
    {
        $data = $request->validate([

        ]);

        $highSchoolStudents->update($data);

        return $highSchoolStudents;
    }

    public function destroy(HighSchoolStudent $highSchoolStudents)
    {
        $highSchoolStudents->delete();

        return response()->json();
    }

    public function summary()
    {
        $paginated = HighSchoolStudent::withCount('choices')
            ->addSelect(['id', 'code', 'name', 'grade_average', 'status'])
            ->whereHas('choices')
            ->paginate(50)
            ->through(fn($student) => [
                'id' => $student->id,
                'code' => $student->code,
                'name' => $student->name,
                'score' => $student->grade_average,
                'status' => $student->status,
                'choices_count' => $student->choices_count,
            ]);

        return $this->ok(
            "Student summary returned successfully",
            $paginated->toArray()
        );
    }
}
