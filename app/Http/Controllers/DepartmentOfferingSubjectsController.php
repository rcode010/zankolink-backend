<?php

namespace App\Http\Controllers;

use App\Models\DepartmentOfferingSubject;
use Illuminate\Http\Request;

class DepartmentOfferingSubjectsController extends Controller
{
    public function index()
    {
        return DepartmentOfferingSubject::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([

        ]);

        return DepartmentOfferingSubject::create($data);
    }

    public function show(DepartmentOfferingSubject $departmentOfferingSubjects)
    {
        return $departmentOfferingSubjects;
    }

    public function update(Request $request, DepartmentOfferingSubject $departmentOfferingSubjects)
    {
        $data = $request->validate([

        ]);

        $departmentOfferingSubjects->update($data);

        return $departmentOfferingSubjects;
    }

    public function destroy(DepartmentOfferingSubject $departmentOfferingSubjects)
    {
        $departmentOfferingSubjects->delete();

        return response()->json();
    }
}
