<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentApplicationRequest;
use App\Http\Resources\StudentApplicationResource;
use App\Jobs\RunAdmissionJob;
use App\Models\AcademicYear;
use App\Models\StudentApplication;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StudentApplicationController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $user = $request->user();

        $academicYear = AcademicYear::where('is_active', 1)->firstOrFail();

        $studentApplicationDraft = StudentApplication::where('student_id', $user->id)
            ->where('academic_year_id', $academicYear->id)
            ->first();

        if (! $studentApplicationDraft) {
            return $this->noData('No data found');
        }

        return $this->ok('Application Draft Retrieved Successfully',
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

    public function show(Request $request)
    {
        $student = $request->user();

        if ($student->accepted_department_offering_id) {
            $offering = $student->acceptedDepartmentOffering()
                ->with(['department.faculty.university'])
                ->first();

            return $this->ok('Application result available.', [
                'status' => 'result',
                'placed' => true,
                'data' => [
                    'department_offering' => $offering,
                    'department' => $offering->department,
                    'faculty' => $offering->department->faculty,
                    'university' => $offering->department->faculty->university,
                ],
            ]);
        }

        $academicYear = AcademicYear::where('is_active', true)->first();
        $cacheKey = RunAdmissionJob::key($academicYear->id);
        $admissionState = Cache::get($cacheKey, [
            'status' => 'idle',
            'message' => null,
        ]);

        // student was not placed
        if ($admissionState['status'] === 'completed') {
            return $this->ok('Admission process completed. You were not placed.', [
                'status' => 'result',
                'placed' => false,
                'data' => null,
            ]);
        }

        // still not evaluated
        return $this->ok('Application submitted, waiting for admission process.', [
            'status' => 'submitted',
            'placed' => null,
            'data' => null,
        ]);
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
