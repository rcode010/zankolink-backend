<?php

namespace App\Http\Controllers;

use App\Models\StudentSubject;
use Illuminate\Http\Request;

class StudentSubjectsController extends Controller
{
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
}
