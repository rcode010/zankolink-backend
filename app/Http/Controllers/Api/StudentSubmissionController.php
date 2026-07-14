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
        $student = auth()->user()->student;

        if (now()->greaterThan($submission->course_assessment->due_at)) {
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
        $teacher = $this->resolveTeacher();
        $this->assertTeacherOwnsSubmission($submission, $teacher);

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
        $studentSubmission->loadMissing(['student.user', 'submission.section']);

        $this->assertCanViewSubmission($studentSubmission);

        return $this->success(
            'Student submission retrieved successfully.',
            (new StudentSubmissionResource($studentSubmission))->resolve()
        );
    }

    /**
     * Resolve the authenticated user's teacher profile.
     */
    private function resolveTeacher()
    {
        $teacher = auth()->user()->teacher;

        abort_unless(
            $teacher,
            403,
            'Only accounts with a teacher profile can access this resource.'
        );

        return $teacher;
    }

    /**
     * Confirm the given teacher is the one assigned to the section.
     */
    private function assertTeacherOwnsSubmission(SectionSubmission $submission, $teacher): void
    {
        $submission->loadMissing('section');

        abort_unless(
            $submission->section && $submission->section->teacher_id === $teacher->id,
            403,
            "You are not the lecturer assigned to this assignment's section."
        );
    }

    /**
     * Authorization for show(): lecturer owns section OR student owns submission.
     */
    private function assertCanViewSubmission(StudentSubmission $studentSubmission): void
    {
        $user = auth()->user();

        $teacher = $user->teacher;
        if ($teacher && $studentSubmission->submission?->section?->teacher_id === $teacher->id) {
            return;
        }

        $student = $user->student;
        if ($student && $studentSubmission->student_id === $student->id) {
            return;
        }

        abort(403, 'You are not authorized to view this submission.');
    }
}
