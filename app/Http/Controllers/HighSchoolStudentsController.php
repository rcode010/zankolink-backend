<?php

namespace App\Http\Controllers;

use App\Models\HighSchoolStudent;
use Illuminate\Http\Request;

class HighSchoolStudentsController extends Controller
{
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
}
