<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentApplicationRequest;
use App\Http\Resources\StudentApplicationResource;
use App\Models\AcademicYear;
use App\Models\StudentApplication;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class StudentApplicationController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $user = $request->user();

        $academicYear = AcademicYear::where('is_active', 1)->firstOrFail();

        $studentApplicationDraft = StudentApplication::where('student_id', $user->id)
            ->where("academic_year_id", $academicYear->id)
            ->first();

        if (!$studentApplicationDraft) {
            return $this->ok("No student application found", null);
        }

        return $this->ok("Application Draft Retrieved Successfully",
            (new StudentApplicationResource($studentApplicationDraft))->resolve());
    }

    public function store(StoreStudentApplicationRequest $request)
    {
        $credential = $request->validated();
        $user = $request->user();

        $application = StudentApplication::updateOrCreate(
            [
                'student_id' => $user->id,
                'academic_year_id' => $credential['academic_year_id'],
            ],
            [
                'status' => 'draft',
                'draft_choices' => $credential['draft_choices'],
                'submitted_at' => null,
            ]
        );

        return $this->ok(
            'Student application created successfully.',
            (new StudentApplicationResource($application))->resolve()
        );
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
