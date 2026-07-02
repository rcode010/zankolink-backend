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

class SectionSubmissionController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
     */
    public function store(StoreSectionSubmissionRequest $request, CourseSection $section)
    {
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $submission = $section->submissions()->create([
                'title' => $data['title'],
                'description' => $data['description'],
                'deadline' => $data['deadline'],
            ]);

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
     * Display the specified resource.
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
     * Update the specified resource in storage.
     */
    public function update(UpdateSectionSubmissionRequest $request, SectionSubmission $submission)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SectionSubmission $submission)
    {
        //
    }
}
