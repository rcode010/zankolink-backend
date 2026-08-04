<?php

namespace App\Http\Controllers;

use App\Models\DepartmentOffering;
use Illuminate\Http\Request;

class DepartmentOfferingsController extends Controller
{
    public function index()
    {
        return DepartmentOffering::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([

        ]);

        return DepartmentOffering::create($data);
    }

    public function show(DepartmentOffering $departmentOfferings)
    {
        return $departmentOfferings;
    }

    public function update(Request $request, DepartmentOffering $departmentOfferings)
    {
        $data = $request->validate([

        ]);

        $departmentOfferings->update($data);

        return $departmentOfferings;
    }

    public function destroy(DepartmentOffering $departmentOfferings)
    {
        $departmentOfferings->delete();

        return response()->json();
    }
}
