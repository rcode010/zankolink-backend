<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GradeStudentSubmissionRequest;
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
 * APIs for student-submission CRUD, plus lecturer submission review and
 * grading (EZK-85).
 *
 * --- ACCESS CONTROL (index, show, grade) ---
 *   - index(), grade(): lecturer-only, must be the teacher assigned to the
 *     section this assignment belongs to.
 *   - show(): lecturer (same ownership check) OR the student who owns
 *     that specific submission — this is what guarantees a student only
 *     ever sees their own grade/feedback, since they can't reach anyone
 *     else's submission through this endpoint.
 *
 * NOTE: `weight` is set/updated on the assignment itself via
 * SectionSubmissionController::store()/update() — NOT here. grade()
 * only ever touches grade + feedback for one student's submission.
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
     * }
     * ]
     * }
     */
    public function store(StoreStudentSubmissionRequest $request, SectionSubmission $submission)
    {
        $this->authorize('create', [StudentSubmission::class, $submission]);
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
                    StudentSubmission::with([
                        'student.user',
                        'submission',
                    ])
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
     */
    public function mySubmission(SectionSubmission $submission)
    {
        $this->authorize('view', $submission);
        $student = auth()->user()->student;

        $studentSubmissions = StudentSubmission::with([
            'student.user',
            'submission',
        ])
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
     * @authenticated
     *
     * @urlParam studentSubmission integer required The ID of the submitted file. Example: 1
     */
    public function download(StudentSubmission $studentSubmission)
    {
        $this->authorize('download', $studentSubmission);
        return Storage::disk('public')->download(
            $studentSubmission->file_url,
            $studentSubmission->file_name
        );
    }

    /**
     * Delete submitted file
     *
     * @authenticated
     *
     * @urlParam studentSubmission integer required The ID of the submitted file. Example: 1
     */
    public function destroy(StudentSubmission $studentSubmission)
    {
        $this->authorize('delete', $studentSubmission);
        Storage::disk('public')->delete($studentSubmission->file_url);

        $studentSubmission->delete();

        return $this->success('Submission deleted successfully.');
    }

    /**
     * List Student Submissions For An Assignment
     *
     * Lists every student's submission for a given assignment.
     * Only the lecturer assigned to that assignment's section may call this.
     *
     * @authenticated
     *
     * @urlParam submission integer required The ID of the assignment. Example: 1
     *
     * @response status=403 scenario="not the assigned lecturer" {
     * "status": "error",
     * "message": "You are not the lecturer assigned to this assignment's section.",
     * "data": null
     * }
     */
    public function index(SectionSubmission $submission)
    {
        $this->authorize('viewAny', [StudentSubmission::class, $submission]);

        $studentSubmissions = StudentSubmission::with([
            'student.user',
            'submission',
        ])
            ->where('submission_id', $submission->id)
            ->latest()
            ->get();

        return $this->success(
            'Student submissions retrieved successfully.',
            StudentSubmissionResource::collection($studentSubmissions)->resolve()
        );
    }

    /**
     * Show One Student Submission
     *
     * Shows a single student submission. Accessible by the lecturer
     * assigned to its section, or by the student who owns it.
     *
     * @authenticated
     *
     * @urlParam studentSubmission integer required The ID of the student submission. Example: 9
     *
     * @response status=403 scenario="not authorized" {
     * "status": "error",
     * "message": "You are not authorized to view this submission.",
     * "data": null
     * }
     */
    public function show(StudentSubmission $studentSubmission)
    {
        $this->authorize('view', [StudentSubmission::class, $studentSubmission]);
        $studentSubmission->loadMissing(['student.user', 'submission.section']);

        return $this->success(
            'Student submission retrieved successfully.',
            (new StudentSubmissionResource($studentSubmission))->resolve()
        );
    }

    /**
     * Grade A Student Submission
     *
     * Assigns a grade and optional feedback to a student's submission.
     * Only the lecturer assigned to the section this assignment belongs to may grade it.
     *
     * NOTE: this endpoint does NOT set the assignment's weight — that's
     * done via SectionSubmissionController::store()/update() when the
     * lecturer creates/edits the assignment itself.
     *
     * @authenticated
     *
     * @urlParam studentSubmission integer required The ID of the student submission. Example: 9
     * @bodyParam grade numeric required The numeric mark (0-100). Example: 85
     * @bodyParam feedback string The lecturer's written feedback. Example: Good work.
     *
     * @response status=403 scenario="not the assigned lecturer" {
     * "status": "error",
     * "message": "You are not the lecturer assigned to this assignment's section.",
     * "data": null
     * }
     */
    public function grade(GradeStudentSubmissionRequest $request, StudentSubmission $studentSubmission)
    {
        $this->authorize('grade', [StudentSubmission::class, $studentSubmission]);

        $teacher = $request->user()->teacher;

        $studentSubmission->loadMissing('submission.section');

        $validated = $request->validated();

        $studentSubmission->update([
            'grade' => $validated['grade'],
            'feedback' => $validated['feedback'] ?? null,
            'graded_at' => now(),
            'graded_by' => $teacher->id,
        ]);

        $studentSubmission->refresh()->load('student.user', 'submission');

        return $this->success(
            'Submission graded successfully.',
            (new StudentSubmissionResource($studentSubmission))->resolve()
        );
    }
}
