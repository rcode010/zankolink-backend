<?php

namespace App\Http\Controllers;

use App\Models\StudentSubject;
use Illuminate\Http\Request;
use App\Traits\ApiResponses;

class StudentSubjectsController extends Controller
{
    use ApiResponses;
    public function index()
    {
        return StudentSubject::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([

        ]);

        return StudentSubject::create($data);
    }

    public function show(StudentSubject $studentSubjects)
    {
        return $studentSubjects;
    }

    public function update(Request $request, StudentSubject $studentSubjects)
    {
        $data = $request->validate([

        ]);

        $studentSubjects->update($data);

        return $studentSubjects;
    }

    public function destroy(StudentSubject $studentSubjects)
    {
        $studentSubjects->delete();

        return response()->json();
    }
    public function getStudentSubjects(Request $request)
    {
        $user = $request->user();

        $studentSubjects = $user->subjects()
            ->withPivot('grade')
            ->get();

        return $this->ok('Retrieved student subjects', $studentSubjects->toArray());
    }
}
