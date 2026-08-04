<?php

namespace App\Http\Controllers;

use App\Models\Subjects;
use Illuminate\Http\Request;

class SubjectsController extends Controller
{
    public function index()
    {
        return Subjects::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([

        ]);

        return Subjects::create($data);
    }

    public function show(Subjects $subjects)
    {
        return $subjects;
    }

    public function update(Request $request, Subjects $subjects)
    {
        $data = $request->validate([

        ]);

        $subjects->update($data);

        return $subjects;
    }

    public function destroy(Subjects $subjects)
    {
        $subjects->delete();

        return response()->json();
    }
}
