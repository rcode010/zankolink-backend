<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentContactInfoRequest;
use App\Http\Resources\StudentContactInfoResource;
use App\Models\StudentContactInfo;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class StudentContactInfoController extends Controller
{
    use ApiResponses;

    public function store(StoreStudentContactInfoRequest $request)
    {
        $credentials = $request->validated();

        $credentials['student_id'] = $request->user()->id;

        $student = StudentContactInfo::create($credentials);

        return $this->created('Student Contact Info created successfully', (new StudentContactInfoResource($student))->resolve());
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
