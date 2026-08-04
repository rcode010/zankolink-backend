<?php

namespace App\Http\Controllers;

use App\Models\DepartmentOfferingSubjects;
use Illuminate\Http\Request;

class DepartmentOfferingSubjectsController extends Controller
{
    public function index()
    {
        return DepartmentOfferingSubjects::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([

        ]);

        return DepartmentOfferingSubjects::create($data);
    }

    public function show(DepartmentOfferingSubjects $departmentOfferingSubjects)
    {
        return $departmentOfferingSubjects;
    }

    public function update(Request $request, DepartmentOfferingSubjects $departmentOfferingSubjects)
    {
        $data = $request->validate([

        ]);

        $departmentOfferingSubjects->update($data);

        return $departmentOfferingSubjects;
    }

    public function destroy(DepartmentOfferingSubjects $departmentOfferingSubjects)
    {
        $departmentOfferingSubjects->delete();

        return response()->json();
    }
}
