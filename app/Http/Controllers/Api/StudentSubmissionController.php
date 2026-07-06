<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentSubmissionRequest;
use App\Http\Resources\StudentSubmissionResource;
use App\Models\SectionSubmission;
use App\Models\StudentSubmission;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentSubmissionController extends Controller
{
    use ApiResponses;

    public function store(StoreStudentSubmissionRequest $request, SectionSubmission $submission)
    {
        $student = auth()->user()->student;

        if (now()->greaterThan($submission->deadline)) {
            return $this->error('Assignment deadline has passed', 422);
        }

        $submission->load('section');

        $isEnrolled = $student->courses()
            ->where('course_id', $submission->section->course_id)
            ->exists();

        if (! $isEnrolled) {
            return $this->error('You are not enrolled in this course', 403);
        }

        DB::beginTransaction();

        try {
            $createdFiles = [];

            foreach ($request->file('files') as $file) {
                $path = $file->store('student-submissions', 'public');

                $createdFiles[] = StudentSubmission::create([
                    'submission_id' => $submission->id,
                    'student_id' => $student->id,

                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'file_url' => $path,
                ]);
            }
            DB::commit();

            return $this->success(
                'Assignment submitted successfully',
                StudentSubmissionResource::collection(
                    StudentSubmission::with('student.user')
                        ->whereIn(
                            'id',
                            collect($createdFiles)->pluck('id')
                        )
                        ->get()
                )->resolve(),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error($e);

            return $this->error('Failed to submit assignment', 500);
        }
    }

    public function mySubmission(SectionSubmission $submission)
    {
        $student = auth()->user()->student;

        $studentSubmissions = StudentSubmission::with('student.user')
            ->where('submission_id', $submission->id)
            ->where('student_id', $student->id)
            ->get();

        return $this->success(
            'Submission retrieved successfully',
            StudentSubmissionResource::collection($studentSubmissions)->resolve()
        );
    }

    public function download(StudentSubmission $studentSubmission)
    {
        return Storage::disk('public')->download(
            $studentSubmission->file_url,
            $studentSubmission->file_name
        );
    }

    public function destroy(StudentSubmission $studentSubmission)
    {
        Storage::disk('public')->delete($studentSubmission->file_url);

        $studentSubmission->delete();

        return $this->success('Submission deleted successfully.');
    }
}
