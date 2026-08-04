<?php

namespace App\Http\Controllers;

use App\Models\StudentSubjects;
use Illuminate\Http\Request;

class StudentSubjectsController extends Controller
{
    public function index()
    {
        return StudentSubjects::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([

        ]);

        return StudentSubjects::create($data);
    }

    public function show(StudentSubjects $studentSubjects)
    {
        return $studentSubjects;
    }

    public function update(Request $request, StudentSubjects $studentSubjects)
    {
        $data = $request->validate([

        ]);

        $studentSubjects->update($data);

        return $studentSubjects;
    }

    public function destroy(StudentSubjects $studentSubjects)
    {
        $studentSubjects->delete();

        return response()->json();
    }
}
