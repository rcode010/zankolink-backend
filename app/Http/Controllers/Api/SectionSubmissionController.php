<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSectionSubmissionRequest;
use App\Http\Requests\UpdateSectionSubmissionRequest;
use App\Http\Resources\SectionSubmissionResource;
use App\Models\CourseSection;
use App\Models\SectionSubmission;
use App\Traits\ApiResponses;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * @group Section-Submission
 *
 * APIs for section-submission CRUD.
 */
class SectionSubmissionController extends Controller
{
    use ApiResponses;

    /**
     * List assignments in a section
     *
     * Returns all assignments belonging to the specified course section.
     *
     * @authenticated
     *
     * @urlParam section integer required The ID of the course section. Example: 1
     *
     * @response 200 {
     * "success": true,
     * "message": "Assignments retrieved successfully.",
     * "data": [
     * {
     * "id": 1,
     * "title": "homework",
     * "description": "this is the description",
     * "deadline": "2026-07-19 17:00:00",
     * "section": {
     * "id": 1,
     * "title": "Week 1: Introduction to Laravel Basics"
     * },
     * "attachments": [
     * {
     * "id": 1,
     * "file_name": "Screenshot 2026-07-04 142426.png",
     * "file_type": "image/png",
     * "file_size": 233,
     * "file_url": "section-submission/mdhyJuDmImO3lGxQ4JPtb13K2BXyIhkWjho1YYvp.png"
     * }
     * ],
     * "created_at": "2026-07-09 10:03:24",
     * "updated_at": "2026-07-09 10:03:24"
     * }
     * ]
     * }
     */
    public function index(CourseSection $section)
    {
        $submission = $section->submissions()->with(['attachments', 'section:id,title'])->get();

        return $this->success(
            'Assignments retrieved successfully.',
            $submission ? (SectionSubmissionResource::collection($submission))->resolve() : null
        );
    }

    /**
     * Create assignment
     *
     * Creates a new assignment for the specified course section.
     * Multiple attachment files may be uploaded.
     *
     * @authenticated
     *
     * @urlParam section integer required The ID of the course section. Example: 1
     *
     * @bodyParam title string required The assignment title. Example: Project 1
     * @bodyParam description string required The assignment description.
     * @bodyParam deadline datetime required Assignment deadline. Example: 2026-10-15 14:30:00
     * @bodyParam files file[] Optional One or more attachment files.
     *
     * @response 201 {
     *  "success": true,
     *  "message": "Assignment created successfully",
     *  "data": {
     *  "id": 1,
     *  "title": "homework",
     *  "description": "this is the description",
     *  "deadline": "2026-07-19 17:00:00",
     *  "section": {
     *  "id": 1,
     *  "title": "Week 1: Introduction to Laravel Basics"
     *  },
     *  "attachments": [
     *  {
     *  "id": 1,
     *  "file_name": "Screenshot 2026-07-04 142426.png",
     *  "file_type": "image/png",
     *  "file_size": 233,
     *  "file_url": "section-submission/mdhyJuDmImO3lGxQ4JPtb13K2BXyIhkWjho1YYvp.png"
     *  }
     *  ],
     *  "created_at": "2026-07-09 10:03:24",
     *  "updated_at": "2026-07-09 10:03:24"
     *  }
     *  }
     */
    public function store(StoreSectionSubmissionRequest $request, CourseSection $section)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $submission = $section->submissions()->create($data);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store('section-submission', 'public');
                    $submission->attachments()->create([
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                        'file_url' => $path,
                    ]);
                }
            }

            DB::commit();

            return $this->success(
                'Assignment created successfully',
                (new SectionSubmissionResource(
                    $submission->load([
                        'section:id,title',
                        'attachments',
                    ])
                ))->resolve(),
                201
            );
        } catch (\Exception $e) {

            DB::rollBack();

            \Log::error(
                'Assignment creation failed: '.$e->getMessage()
            );

            return $this->error(
                'Failed to create assignment.',
                500
            );
        }
    }

    /**
     * Show assignment details
     *
     * Returns the details of a specific assignment.
     *
     * @authenticated
     *
     * @urlParam submission integer required The ID of the assignment. Example: 1
     *
     * @response 200 {
     * "success": true,
     * "message": "Assignment retrieved successfully.",
     * "data": {
     * "id": 1,
     * "title": "homework",
     * "description": "this is the description",
     * "deadline": "2026-07-19 17:00:00",
     * "section": {
     * "id": 1,
     * "title": "Week 1: Introduction to Laravel Basics"
     * },
     * "attachments": [
     * {
     * "id": 1,
     * "file_name": "Screenshot 2026-07-04 142426.png",
     * "file_type": "image/png",
     * "file_size": 233,
     * "file_url": "section-submission/mdhyJuDmImO3lGxQ4JPtb13K2BXyIhkWjho1YYvp.png"
     * }
     * ],
     * "created_at": "2026-07-09 10:03:24",
     * "updated_at": "2026-07-09 10:03:24"
     * }
     * }
     */
    public function show(SectionSubmission $submission)
    {
        return $this->success(
            'Assignment retrieved successfully.',
            (new SectionSubmissionResource($submission->load([
                'attachments',
                'section:id,title',
            ])
            ))->resolve()
        );
    }

    /**
     * Update assignment
     *
     * Updates an existing assignment.
     * Additional attachment files may also be uploaded.
     *
     * @authenticated
     *
     * @urlParam submission integer required The ID of the assignment. Example: 1
     *
     * @bodyParam title string The assignment title. Example: Updated Project
     * @bodyParam description string The assignment description.
     * @bodyParam deadline datetime Assignment deadline. Example: 2026-10-20 16:00:00
     * @bodyParam files file[] Optional One or more attachment files.
     *
     * @response 200 {
     * "success": true,
     * "message": "Assignment updated successfully.",
     * "data": {
     * "id": 1,
     * "title": "new title",
     * "description": "new description",
     * "deadline": "01-08-2026",
     * "section": {
     * "id": 1,
     * "title": "Week 1: Introduction to Laravel Basics"
     * },
     * "attachments": [
     * {
     * "id": 1,
     * "file_name": "Screenshot 2026-07-04 142426.png",
     * "file_type": "image/png",
     * "file_size": 233,
     * "file_url": "section-submission/mdhyJuDmImO3lGxQ4JPtb13K2BXyIhkWjho1YYvp.png"
     * },
     * {
     * "id": 2,
     * "file_name": "Screenshot 2026-07-04 142426.png",
     * "file_type": "image/png",
     * "file_size": 233,
     * "file_url": "section-submission/KGq4P6aFWYL9o8Vvj2yEZQusc3HS8sSuiRiI37Ut.png"
     * }
     * ],
     * "created_at": "2026-07-09 10:03:24",
     * "updated_at": "2026-07-09 10:05:35"
     * }
     * }
     */
    public function update(UpdateSectionSubmissionRequest $request, SectionSubmission $submission)
    {
        $data = $request->validated();
        DB::beginTransaction();
        try {
            $submission->update($data);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store('section-submission', 'public');
                    $submission->attachments()->create([
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                        'file_url' => $path,
                    ]);
                }
            }

            DB::commit();

            return $this->success(
                'Assignment updated successfully.',
                (new SectionSubmissionResource($submission->load([
                    'section:id,title',
                    'attachments',
                ])))->resolve()
            );
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error($e);

            return $this->error(
                'Failed to update assignment.',
                500
            );
        }
    }

    /**
     * Delete assignment
     *
     * Deletes an assignment and all of its attachments.
     *
     * @authenticated
     *
     * @urlParam submission integer required The ID of the assignment. Example: 1
     *
     * @response 200 {
     * "success": true,
     * "message": "Assignment deleted successfully.",
     * "data": []
     * }
     */
    public function destroy(SectionSubmission $submission)
    {
        foreach ($submission->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_url);
        }
        $submission->delete();

        return $this->success(
            'Assignment deleted successfully.',
        );
    }
}
