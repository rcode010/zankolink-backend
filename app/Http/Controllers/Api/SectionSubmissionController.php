<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSectionSubmissionRequest;
use App\Http\Requests\UpdateSectionSubmissionRequest;
use App\Models\CourseSection;
use App\Models\SectionSubmission;
use Illuminate\Http\Request;

class SectionSubmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(CourseSection $section)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSectionSubmissionRequest $request, CourseSection $section)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(SectionSubmission $submission)
    {
        //
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
