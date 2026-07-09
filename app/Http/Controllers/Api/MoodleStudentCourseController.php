<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SectionSubmissionResource;
use App\Models\SectionSubmission;
use App\Models\StudentSubmission;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * @group Moodle Student Submissions
 *
 * Managing endpoints for students submitting their assignments and viewing their grades,
 * alongside teacher-facing dashboard features to list and grade submissions.
 *
 * --- ACCESS CONTROL ---
 * 1. Resolves the active student profile dynamically from the authenticated session.
 * 2. Formatted specifically for a robust frontend state integration without deep resource nesting.
 */
class StudentSubmissionController extends Controller
{
    use ApiResponses;

    /**
     * List Assignment Submissions
     *
     * List all student submissions for a specific assignment blueprint (Lecturer-facing).
     *
     * @authenticated
     * @urlParam submission integer required The ID of the assignment blueprint. Example: 4
     */
    public function index(Request $request, SectionSubmission $submission)
    {
        $submissions = $submission->studentSubmissions()
            ->with('student.user:id,name')
            ->latest()
            ->get();

        return $this->ok('Student submissions retrieved successfully.', $submissions);
    }

    /**
     * Upload Assignment Submission
     *
     * Submit a file upload response for a specific assignment blueprint (Student-facing).
     *
     * @authenticated
     * @urlParam submission integer required The ID of the assignment blueprint. Example: 4
     */
    public function store(Request $request, SectionSubmission $submission)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,zip,rar|max:10240',
        ]);

        $student = $this->resolveStudent($request);

        $exists = $submission->studentSubmissions()->where('student_id', $student->id)->exists();
        if ($exists) {
            return $this->error('You have already submitted this assignment.', 400);
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('student-submissions', 'public');

            $studentSubmission = $submission->studentSubmissions()->create([
                'student_id'   => $student->id,
                'submitted_at' => now(),
                'file_name'    => $file->getClientOriginalName(),
                'file_type'    => $file->getClientMimeType(),
                'file_size'    => $file->getSize(),
                'file_url'     => $path,
            ]);

            return $this->success('Assignment submitted successfully.', $studentSubmission, 201);
        }

        return $this->error('File upload failed.', 400);
    }

    /**
     * Show Student Submission details
     *
     * Fetch explicit operational details of a single student answer for marking (Lecturer-facing).
     *
     * @authenticated
     * @urlParam studentSubmission integer required The unique response record ID. Example: 6
     */
    public function show(Request $request, StudentSubmission $studentSubmission)
    {
        return $this->ok('Student submission retrieved successfully.', $studentSubmission);
    }

    /**
     * View My Personal Submission
     *
     * View flattened response blueprint details for a single assignment (Student-facing View Modal).
     *
     * @authenticated
     * @urlParam submission integer required The ID of the assignment blueprint. Example: 4
     */
    public function mySubmission(Request $request, SectionSubmission $submission)
    {
        $student = $this->resolveStudent($request);

        $studentSubmission = $submission->studentSubmissions()
            ->where('student_id', $student->id)
            ->first();

        if (! $studentSubmission) {
            return $this->ok('You have not submitted this assignment yet.', null);
        }

        return $this->ok('Submission retrieved successfully', [
            'id'           => $studentSubmission->id,
            'student_id'   => $studentSubmission->student_id,
            'submitted_at' => $studentSubmission->created_at?->toDateTimeString(),
            
            'grade'        => $studentSubmission->grade,
            'feedback'     => $studentSubmission->feedback,
            'graded_at'    => $studentSubmission->graded_at,
            
            'attachment'   => [
                'file_name' => $studentSubmission->file_name,
                'file_type' => $studentSubmission->file_type,
                'file_size' => $studentSubmission->file_size,
                'file_url'  => $studentSubmission->file_url ? Storage::disk('public')->url($studentSubmission->file_url) : null,
            ]
        ]);
    }

    /**
     * Grade Student Submission
     *
     * Update validation markers, evaluate and grade a student's answer document (Lecturer-facing).
     *
     * @authenticated
     * @urlParam studentSubmission integer required The unique response record ID. Example: 6
     */
    public function grade(Request $request, StudentSubmission $studentSubmission)
    {
        $request->validate([
            'grade'    => 'required|numeric|min:0',
            'feedback' => 'nullable|string',
        ]);

        $studentSubmission->update([
            'grade'     => $request->grade,
            'feedback'  => $request->feedback,
            'graded_at' => now(),
        ]);

        return $this->ok('Submission graded successfully.', $studentSubmission);
    }

    /**
     * Download Submitted File Attachment
     *
     * Download secure attachment files locally from public local disk mappings.
     *
     * @authenticated
     */
    public function download(Request $request, StudentSubmission $studentSubmission)
    {
        if (! Storage::disk('public')->exists($studentSubmission->file_url)) {
            return $this->error('File not found on storage.', 404);
        }

        return Storage::disk('public')->download($studentSubmission->file_url, $studentSubmission->file_name);
    }

    /**
     * Delete Submission Record
     *
     * Remove student records alongside mapped resource attachments.
     *
     * @authenticated
     */
    public function destroy(Request $request, StudentSubmission $studentSubmission)
    {
        if ($studentSubmission->file_url) {
            Storage::disk('public')->delete($studentSubmission->file_url);
        }

        $studentSubmission->delete();

        return $this->ok('Submission deleted successfully.', null);
    }

    /**
     * Resolve the authenticated user's student profile securely.
     */
    private function resolveStudent(Request $request)
    {
        $student = $request->user()->student;

        abort_unless(
            $student,
            403,
            'Only accounts with a student profile can access this resource.'
        );

        return $student;
    }
}