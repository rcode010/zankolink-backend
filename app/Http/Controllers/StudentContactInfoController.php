<?php

namespace App\Http\Controllers;

use App\Models\StudentContactInfo;
use Illuminate\Http\Request;

class StudentContactInfoController extends Controller
{
    public function index()
    {
        return StudentContactInfo::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([

        ]);

        return StudentContactInfo::create($data);
    }

    public function show(StudentContactInfo $studentContactInfo)
    {
        return $studentContactInfo;
    }

    public function update(Request $request, StudentContactInfo $studentContactInfo)
    {
        $data = $request->validate([

        ]);

        $studentContactInfo->update($data);

        return $studentContactInfo;
    }

    public function destroy(StudentContactInfo $studentContactInfo)
    {
        $studentContactInfo->delete();

        return response()->json();
    }
}
