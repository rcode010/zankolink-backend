<?php

namespace App\Http\Controllers;

use App\Models\HighSchoolStudents;
use Illuminate\Http\Request;

class HighSchoolStudentsController extends Controller
{
    public function index()
    {
        return HighSchoolStudents::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([

        ]);

        return HighSchoolStudents::create($data);
    }

    public function show(HighSchoolStudents $highSchoolStudents)
    {
        return $highSchoolStudents;
    }

    public function update(Request $request, HighSchoolStudents $highSchoolStudents)
    {
        $data = $request->validate([

        ]);

        $highSchoolStudents->update($data);

        return $highSchoolStudents;
    }

    public function destroy(HighSchoolStudents $highSchoolStudents)
    {
        $highSchoolStudents->delete();

        return response()->json();
    }
}
