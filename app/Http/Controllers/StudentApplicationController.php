<?php

namespace App\Http\Controllers;

use App\Models\StudentApplication;
use Illuminate\Http\Request;

class StudentApplicationController extends Controller
{
    public function index()
    {
        return StudentApplication::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([

        ]);

        return StudentApplication::create($data);
    }

    public function show(StudentApplication $studentApplication)
    {
        return $studentApplication;
    }

    public function update(Request $request, StudentApplication $studentApplication)
    {
        $data = $request->validate([

        ]);

        $studentApplication->update($data);

        return $studentApplication;
    }

    public function destroy(StudentApplication $studentApplication)
    {
        $studentApplication->delete();

        return response()->json();
    }
}
