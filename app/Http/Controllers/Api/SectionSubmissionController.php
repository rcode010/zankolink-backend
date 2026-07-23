<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSectionSubmissionRequest;
use App\Http\Requests\UpdateSectionSubmissionRequest;
use App\Http\Resources\SectionSubmissionResource;
use App\Models\CourseSection;
use App\Models\SectionSubmission;
use App\Services\SectionSubmissionService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

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
     * "file_url": "http://localhost/storage/section-submission/mdhyJuDmImO3lGxQ4JPtb13K2BXyIhkWjho1YYvp.png"
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
        $this->authorize('viewAny', [SectionSubmission::class, $section]);
        $submission = $section->submissions()->with(['attachments', 'section:id,title', 'courseAssessment'])->get();

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
     *  "file_url": "http://localhost/storage/section-submission/mdhyJuDmImO3lGxQ4JPtb13K2BXyIhkWjho1YYvp.png"
     *  }
     *  ],
     *  "created_at": "2026-07-09 10:03:24",
     *  "updated_at": "2026-07-09 10:03:24"
     *  }
     *  }
     */
    public function store(StoreSectionSubmissionRequest $request, CourseSection $section, SectionSubmissionService $service)
    {
        $this->authorize('create', [SectionSubmission::class, $section]);
        $teacher = $request->user()->teacher;
        try {
            $submission = $service->create($section, $request->validated(), $request->file('files', []), $teacher);

            return $this->success(
                'Assignment created successfully',
                (new SectionSubmissionResource($submission->load('creator')))->resolve(),
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
     * "file_url": "http://localhost/storage/section-submission/mdhyJuDmImO3lGxQ4JPtb13K2BXyIhkWjho1YYvp.png"
     * }
     * ],
     * "created_at": "2026-07-09 10:03:24",
     * "updated_at": "2026-07-09 10:03:24"
     * }
     * }
     */
    public function show(SectionSubmission $submission)
    {
        $this->authorize('view', $submission);

        return $this->success(
            'Assignment retrieved successfully.',
            (new SectionSubmissionResource($submission->load([
                'attachments',
                'section:id,title',
                'courseAssessment',
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
     * "file_url": "http://localhost/storage/section-submission/mdhyJuDmImO3lGxQ4JPtb13K2BXyIhkWjho1YYvp.png"
     * },
     * {
     * "id": 2,
     * "file_name": "Screenshot 2026-07-04 142426.png",
     * "file_type": "image/png",
     * "file_size": 233,
     * "file_url": "http://localhost/storage/section-submission/KGq4P6aFWYL9o8Vvj2yEZQusc3HS8sSuiRiI37Ut.png"
     * }
     * ],
     * "created_at": "2026-07-09 10:03:24",
     * "updated_at": "2026-07-09 10:05:35"
     * }
     * }
     */
    public function update(UpdateSectionSubmissionRequest $request, SectionSubmission $submission, SectionSubmissionService $service)
    {
        $this->authorize('update', $submission);

        try {
            $submission = $service->update($submission, $request->validated(), $request->file('files', []));

            return $this->success(
                'Assignment updated successfully.',
                (new SectionSubmissionResource($submission->load([
                    'section:id,title',
                    'attachments',
                    'courseAssessment',
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
        $this->authorize('delete', $submission);

        foreach ($submission->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->file_url);
        }
        $submission->courseAssessment()->delete();
        $submission->delete();

        return $this->success(
            'Assignment deleted successfully.',
        );
    }

    /**
     * Get my assignments
     *
     * Retrieve all upcoming assignments for the authenticated student from their enrolled courses.
     *
     * This endpoint returns section submissions that have a deadline and belong to courses
     * the authenticated student is enrolled in. It also includes the section, course details,
     * and the student's submission if they have already submitted.
     *
     * @group Moodle Section Submissions
     *
     * @authenticated
     *
     * @queryParam filter[course_section_id] integer Filter assignments by course section ID. Example: 1
     * @queryParam sort string Sort assignments by deadline. Use `deadline` for ascending or `-deadline` for descending. Example: deadline
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Assignments retrieved successfully.",
     *   "data": [
     *     {
     *       "id": 2,
     *       "course_section_id": 1,
     *       "title": "homework",
     *       "description": "this is the description",
     *       "deadline": "2026-07-14T21:00:00.000000Z",
     *       "created_at": "2026-07-12T07:00:32.000000Z",
     *       "updated_at": "2026-07-12T07:00:32.000000Z",
     *       "section": {
     *         "id": 1,
     *         "course_id": 1,
     *         "teacher_id": 91,
     *         "title": "Week 1: Introduction to Laravel Basics",
     *         "created_at": "2026-07-12T06:59:16.000000Z",
     *         "updated_at": "2026-07-12T06:59:16.000000Z",
     *         "deleted_at": null,
     *         "course": {
     *           "id": 1,
     *           "name": "Web Development",
     *           "code": "UOS72410"
     *         }
     *       },
     *       "student_submissions": []
     *     }
     *   ]
     * }
     */
    public function myAssignments(Request $request)
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Student profile not found.', 404);
        }

        $query = QueryBuilder::for(
            SectionSubmission::query()
                ->select('section_submissions.*')
                ->join(
                    'course_assessments',
                    'course_assessments.id',
                    '=',
                    'section_submissions.course_assessment_id'
                )
                ->whereNull('course_assessments.deleted_at')
                ->whereNotNull('course_assessments.due_at')
                ->where('course_assessments.due_at', '>=', now())
                ->whereHas('section.course.students', function ($query) use ($student) {
                    $query->where('students.id', $student->id);
                })
        )
            ->with([
                'section.course:id,name,code',

                'courseAssessment:id,course_id,title,max_mark,weight,due_at,academic_year_id',

                'studentSubmissions' => fn ($query) => $query
                    ->where('student_id', $student->id),
            ])
            ->allowedFilters(
                AllowedFilter::exact(
                    'course_section_id',
                    'section_submissions.course_section_id'
                ),

                AllowedFilter::exact(
                    'course_assessment_id',
                    'section_submissions.course_assessment_id'
                ),

                AllowedFilter::exact(
                    'academic_year_id',
                    'course_assessments.academic_year_id'
                ),
            )
            ->allowedSorts(
                AllowedSort::field(
                    'due_at',
                    'course_assessments.due_at'
                ),
            );

        /*
         * Apply the nearest due date first when the frontend
         * does not provide a sort parameter.
         */
        if (! $request->filled('sort')) {
            $query->orderBy('course_assessments.due_at');
        }

        $assignments = $query->get();

        return $this->ok(
            'Assignments retrieved successfully.',
            $assignments->toArray()
        );
    }
}
