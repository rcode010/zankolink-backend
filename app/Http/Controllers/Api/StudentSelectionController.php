<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseSelectionRequest;
use App\Models\Course;
use App\Services\CourseSelectionService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

/**
 * @group Student-Selection
 *
 * APIs for student-selection CRUD.
 */
class StudentSelectionController extends Controller
{
    use ApiResponses;

    /**
     * Submit course selection
     *
     * Submits a course selection for the authenticated student for a given
     * academic year.
     *
     * Stage 1 students require no course_ids — every mandatory course for
     * their department and year level is enrolled automatically, and the
     * student is enrolled immediately with no approval step.
     *
     * Stage 2+ students must submit course_ids. At most one elective may
     * be included. The selection is saved with a pending status and does
     * not enroll the student yet — a department head must approve it
     * separately before it becomes real enrollment. Submitting again
     * before approval replaces the previous pending selection. Submitting
     * again after approval is not allowed.
     *
     * @authenticated
     *
     * @bodyParam academic_year_id integer required The ID of the academic year. Example: 3
     * @bodyParam course_ids integer[] Required for Stage 2+ only, ignored for Stage 1. Must not contain duplicates, and each ID must belong to an active course in the student's own department and year level. Example: [12, 15]
     *
     * @response 200 scenario="Stage 1 student auto-enrolled" {
     *   "message": "Stage 1 student successfully auto-enrolled in all mandatory courses."
     * }
     * @response 200 scenario="Stage 2+ selection saved, pending approval" {
     *   "message": "Course selection saved successfully and is pending department approval."
     * }
     * @response 404 scenario="authenticated user has no student profile" {
     *   "message": "Student record not found."
     * }
     * @response 422 scenario="Stage 1, no mandatory courses configured for this department/year" {
     *   "message": "No mandatory courses found for Stage 1."
     * }
     * @response 422 scenario="Stage 2+, course_ids missing from the request" {
     *   "message": "The course ids field is required for this stage."
     * }
     * @response 422 scenario="selection already approved, cannot resubmit" {
     *   "message": "Your course selection for this academic year has already been approved and can no longer be changed here. Please contact your department."
     * }
     * @response 422 scenario="one or more course_ids do not belong to this student's department/year or are inactive" {
     *   "message": "One or more selected courses are not available for your department or stage.",
     *   "invalid_course_ids": [999]
     * }
     * @response 422 scenario="more than one elective course selected" {
     *   "message": "You cannot select more than one elective course."
     * }
     * @response 422 scenario="the selected elective course has no seats remaining" {
     *   "message": "Sorry, the elective course (AI) is full!"
     * }
     * @response 422 scenario="validation failed, e.g. duplicate course_ids or an unknown academic_year_id" {
     *   "message": "The course ids.0 field has a duplicate value.",
     *   "errors": {
     *     "course_ids.0": ["The course ids.0 field has a duplicate value."]
     *   }
     * }
     */
    public function saveCourseSelection(StoreCourseSelectionRequest $request, CourseSelectionService $service)
    {
        $service->store(
            $request->user(),
            $request->validated()
        );

        return $this->success(
            'Course selection submitted successfully.'
        );
    }
}
