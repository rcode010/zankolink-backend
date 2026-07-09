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

/**
 * @group Student-Submission
 *
 * APIs for student-submission CRUD.
 */
class StudentSubmissionController extends Controller
{
    use ApiResponses;

    /**
     * Submit assignment work
     *
     * Upload one or more files for an assignment.
     * The student must be enrolled in the course and the assignment deadline
     * must not have passed.
     *
     * @authenticated
     *
     * @urlParam submission integer required The ID of the assignment. Example: 1
     *
     * @bodyParam files file[] required One or more files to submit.
     *
     * @response 201 {
     * "success": true,
     * "message": "Assignment submitted successfully",
     * "data": [
     * {
     * "id": 1,
     * "student": {
     * "id": 3,
     * "name": "Wilton Morar III"
     * },
     * "submission_id": 2,
     * "file_name": "Screenshot 2026-07-04 142426.png",
     * "file_type": "image/png",
     * "file_size": 233,
     * "file_url": "student-submissions/g5Npk4t6LxqNnoJYQMbXQ2bmG4zbaqpP8zc4VQpj.png",
     * "created_at": "2026-07-09 10:11:49",
     * "updated_at": "2026-07-09 10:11:49"
     * },
     * {
     * "id": 2,
     * "student": {
     * "id": 3,
     * "name": "Wilton Morar III"
     * },
     * "submission_id": 2,
     * "file_name": "Screenshot 2026-07-04 142426.png",
     * "file_type": "image/png",
     * "file_size": 233,
     * "file_url": "student-submissions/Ynrmmrog16q3SKjY2Kn2xLWdIDnkFapk6sGDPWUk.png",
     * "created_at": "2026-07-09 10:11:49",
     * "updated_at": "2026-07-09 10:11:49"
     * }
     * ]
     * }
     */
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

    /**
     * Show authenticated student's submission
     *
     * Returns all files submitted by the authenticated student
     * for the specified assignment.
     *
     * @authenticated
     *
     * @urlParam submission integer required The ID of the assignment. Example: 1
     *
     * @response 200 {
     * "success": true,
     * "message": "Submission retrieved successfully",
     * "data": [
     * {
     * "id": 1,
     * "student": {
     * "id": 3,
     * "name": "Wilton Morar III"
     * },
     * "submission_id": 2,
     * "file_name": "Screenshot 2026-07-04 142426.png",
     * "file_type": "image/png",
     * "file_size": 233,
     * "file_url": "student-submissions/g5Npk4t6LxqNnoJYQMbXQ2bmG4zbaqpP8zc4VQpj.png",
     * "created_at": "2026-07-09 10:11:49",
     * "updated_at": "2026-07-09 10:11:49"
     * },
     * {
     * "id": 2,
     * "student": {
     * "id": 3,
     * "name": "Wilton Morar III"
     * },
     * "submission_id": 2,
     * "file_name": "Screenshot 2026-07-04 142426.png",
     * "file_type": "image/png",
     * "file_size": 233,
     * "file_url": "student-submissions/Ynrmmrog16q3SKjY2Kn2xLWdIDnkFapk6sGDPWUk.png",
     * "created_at": "2026-07-09 10:11:49",
     * "updated_at": "2026-07-09 10:11:49"
     * }
     * ]
     * }
     */
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

    /**
     * Download submitted file
     *
     * Downloads a submitted assignment file.
     *
     * @authenticated
     *
     * @urlParam studentSubmission integer required The ID of the submitted file. Example: 1
     *
     * @response 200 scenario="File download"
     */
    public function download(StudentSubmission $studentSubmission)
    {
        return Storage::disk('public')->download(
            $studentSubmission->file_url,
            $studentSubmission->file_name
        );
    }

    /**
     * Delete submitted file
     *
     * Deletes one uploaded file from the student's assignment submission.
     *
     * @authenticated
     *
     * @urlParam studentSubmission integer required The ID of the submitted file. Example: 1
     *
     * @response 200 {
     * "success": true,
     * "message": "Submission deleted successfully.",
     * "data": []
     * }
     */
    public function destroy(StudentSubmission $studentSubmission)
    {
        Storage::disk('public')->delete($studentSubmission->file_url);

        $studentSubmission->delete();

        return $this->success('Submission deleted successfully.');
    }
}
