<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrarUpdateContactInfoRequest;
use App\Http\Requests\StoreStudentContactInfoRequest;
use App\Http\Requests\UpdateStudentContactInfoRequest;
use App\Http\Resources\StudentContactInfoResource;
use App\Models\HighSchoolStudent;
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

    public function show(Request $request)
    {
        $studentContact = $request->user()->contacts;

        if (! $studentContact) {
            return $this->ok('No contact info found', null);
        }

        return $this->ok(
            'Student contact information retrieved successfully.',
            (new StudentContactInfoResource($studentContact))->resolve()
        );
    }

    public function update(UpdateStudentContactInfoRequest $request)
    {
        $contactInfo = $request->user()->contacts;

        $contactInfo->update(
            $request->validated()
        );

        return $this->ok(
            'Student contact information updated successfully.',
            (new StudentContactInfoResource($contactInfo->fresh()))->resolve()
        );
    }

    public function destroy(StudentContactInfo $studentContactInfo)
    {
        $studentContactInfo->delete();

        return response()->json();
    }

    public function updateContactInfo(RegistrarUpdateContactInfoRequest $request, HighSchoolStudent $highSchoolStudent)
    {

        $contactInfo = $highSchoolStudent->contacts;

        $contactInfo->update($request->validated());

        return $this->ok(
            'Student contact information updated successfully.',
            (new StudentContactInfoResource($contactInfo->fresh()))->resolve()
        );
    }
}
