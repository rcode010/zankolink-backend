<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectsController extends Controller
{
    public function index()
    {
        return Subject::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([

        ]);

        return Subject::create($data);
    }

    public function show(Subject $subjects)
    {
        return $subjects;
    }

    public function update(Request $request, Subject $subjects)
    {
        $data = $request->validate([

        ]);

        $subjects->update($data);

        return $subjects;
    }

    public function destroy(Subject $subjects)
    {
        $subjects->delete();

        return response()->json();
    }
}
