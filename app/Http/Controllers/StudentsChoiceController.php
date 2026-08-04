<?php

namespace App\Http\Controllers;

use App\Models\StudentsChoice;
use Illuminate\Http\Request;

class StudentsChoiceController extends Controller
{
    public function index()
    {
        return StudentsChoice::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([

        ]);

        return StudentsChoice::create($data);
    }

    public function show(StudentsChoice $studentsChoice)
    {
        return $studentsChoice;
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
