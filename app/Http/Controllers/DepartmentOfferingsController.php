<?php

namespace App\Http\Controllers;

use App\Models\DepartmentOfferings;
use Illuminate\Http\Request;

class DepartmentOfferingsController extends Controller
{
    public function index()
    {
        return DepartmentOfferings::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([

        ]);

        return DepartmentOfferings::create($data);
    }

    public function show(DepartmentOfferings $departmentOfferings)
    {
        return $departmentOfferings;
    }

    public function update(Request $request, DepartmentOfferings $departmentOfferings)
    {
        $data = $request->validate([

        ]);

        $departmentOfferings->update($data);

        return $departmentOfferings;
    }

    public function destroy(DepartmentOfferings $departmentOfferings)
    {
        $departmentOfferings->delete();

        return response()->json();
    }
}
