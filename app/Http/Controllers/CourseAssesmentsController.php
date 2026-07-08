<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseAssessmentRequest;
use App\Http\Requests\UpdateCourseAssessmentRequest;
use App\Models\Course;
use App\Models\CourseAssessments;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Course Assessments
 *
 * APIs for managing course assessments such as quizzes, assignments, midterms, finals, projects, and activities.
 */
class CourseAssesmentsController extends Controller
{
    use ApiResponses;

    /**
     * List course assessments
     *
     * Retrieve all assessments for a specific course. Supports filtering by title and assessment type.
     *
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 1
     *
     * @queryParam filter[title] string Filter assessments by partial title. Example: Quiz
     * @queryParam filter[type] string Filter assessments by exact type. Must be one of: quiz, assignment, final, midterm, project, activity. Example: quiz
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Course Assessments retrieved successfully",
     *   "data": [
     *     {
     *       "id": 1,
     *       "course_id": 1,
     *       "teacher_id": 2,
     *       "academic_year_id": 1,
     *       "title": "Quiz 1",
     *       "type": "quiz",
     *       "max_mark": "10.00",
     *       "weight": "5.00",
     *       "due_at": "2026-07-20 10:00:00",
     *       "is_published": true,
     *       "created_at": "2026-07-08T09:00:00.000000Z",
     *       "updated_at": "2026-07-08T09:00:00.000000Z"
     *     }
     *   ]
     * }
     */
    public function index(Request $request, Course $course)
    {

        $assessments = QueryBuilder::for(CourseAssessments::class)
            ->where('course_id', $course->id)
            ->allowedFilters(
                AllowedFilter::partial('title'),
                AllowedFilter::exact('type'),
            )->get();

        return $this->ok('Course Assessments retrieved successfully', $assessments->toArray());
    }

    /**
     * Create course assessment
     *
     * Create a new assessment for a specific course. The authenticated user must be a teacher.
     *
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 1
     *
     * @bodyParam academic_year_id integer required The ID of the academic year. Example: 1
     * @bodyParam title string required The assessment title. Example: Quiz 1
     * @bodyParam type string required The assessment type. Must be one of: quiz, assignment, final, midterm, project, activity. Example: quiz
     * @bodyParam max_mark number required The maximum mark for this assessment. Example: 10
     * @bodyParam weight number required The assessment weight. Example: 5
     * @bodyParam due_at datetime nullable The due date and time of the assessment. Example: 2026-07-20 10:00:00
     * @bodyParam is_published boolean required Whether the assessment is visible/published. Example: true
     *
     * @response 201 {
     *   "success": true,
     *   "message": "Course Assessment created successfully",
     *   "data": {
     *     "id": 1,
     *     "course_id": 1,
     *     "teacher_id": 2,
     *     "academic_year_id": 1,
     *     "title": "Quiz 1",
     *     "type": "quiz",
     *     "max_mark": "10.00",
     *     "weight": "5.00",
     *     "due_at": "2026-07-20 10:00:00",
     *     "is_published": true,
     *     "created_at": "2026-07-08T09:00:00.000000Z",
     *     "updated_at": "2026-07-08T09:00:00.000000Z"
     *   }
     * }
     * @response 422 {
     *   "message": "The title field is required.",
     *   "errors": {
     *     "title": ["The title field is required."]
     *   }
     * }
     */
    public function store(StoreCourseAssessmentRequest $request, Course $course)
    {
        $credentials = $request->validated();
        $teacher = $request->user()->teacher;
        $courseAssessment = CourseAssessments::create([
            'course_id' => $course->id,
            'academic_year_id' => $credentials['academic_year_id'],
            'type' => $credentials['type'],
            'max_mark' => $credentials['max_mark'],
            'title' => $credentials['title'],
            'weight' => $credentials['weight'],
            'due_at' => $credentials['due_at'] ?? null,
            'teacher_id' => $teacher->id,
            'is_published' => $credentials['is_published'] ?? false,
        ]);

        return $this->created('Course Assessment created successfully', $courseAssessment->toArray());

    }

    /**
     * Update course assessment
     *
     * Update an existing course assessment. The assessment must belong to the given course.
     *
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 1
     * @urlParam assessment integer required The ID of the assessment. Example: 1
     *
     * @bodyParam academic_year_id integer optional The ID of the academic year. Example: 1
     * @bodyParam title string optional The assessment title. Example: Midterm Exam
     * @bodyParam type string optional The assessment type. Must be one of: quiz, assignment, final, midterm, project, activity. Example: midterm
     * @bodyParam max_mark number optional The maximum mark for this assessment. Example: 30
     * @bodyParam weight number optional The assessment weight. Example: 20
     * @bodyParam due_at datetime nullable The due date and time of the assessment. Example: 2026-08-01 09:00:00
     * @bodyParam is_published boolean optional Whether the assessment is visible/published. Example: true
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Course Assessment updated successfully",
     *   "data": {
     *     "id": 1,
     *     "course_id": 1,
     *     "teacher_id": 2,
     *     "academic_year_id": 1,
     *     "title": "Midterm Exam",
     *     "type": "midterm",
     *     "max_mark": "30.00",
     *     "weight": "20.00",
     *     "due_at": "2026-08-01 09:00:00",
     *     "is_published": true
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Assessment does not belong to this course."
     * }
     */
    public function update(UpdateCourseAssessmentRequest $request, Course $course, CourseAssessments $assessment)
    {
        if ((int) $assessment->course_id !== (int) $course->id) {
            return $this->error('Assessment does not belong to this course.', 404);
        }
        $credentials = $request->validated();

        $assessment->update($credentials);

        return $this->ok('Course Assessment updated successfully', $assessment->toArray());

    }

    /**
     * Show course assessment
     *
     * Retrieve a specific course assessment with its course, teacher, and academic year details.
     *
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 1
     * @urlParam assessment integer required The ID of the assessment. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Course Assessment retrieved successfully",
     *   "data": {
     *     "id": 1,
     *     "course_id": 1,
     *     "teacher_id": 2,
     *     "academic_year_id": 1,
     *     "title": "Quiz 1",
     *     "type": "quiz",
     *     "max_mark": "10.00",
     *     "weight": "5.00",
     *     "due_at": "2026-07-20 10:00:00",
     *     "is_published": true,
     *     "course": {
     *       "id": 1,
     *       "name": "Database Systems"
     *     },
     *     "teacher": {
     *       "id": 2,
     *       "user_id": 5,
     *       "user": {
     *         "id": 5,
     *         "name": "Teacher Name"
     *       }
     *     },
     *     "academic_year": {
     *       "id": 1,
     *       "year": "2026-2027"
     *     }
     *   }
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Assessment does not belong to this course."
     * }
     */
    public function show(Course $course, CourseAssessments $assessment)
    {
        if ((int) $assessment->course_id !== (int) $course->id) {
            return $this->error('Assessment does not belong to this course.', 404);
        }

        $assessment->load([
            'course:id,name',
            'teacher:id,user_id',
            'teacher.user:id,name',
            'academicYear:id,year',
        ]);

        return $this->ok(
            'Course Assessment retrieved successfully',
            $assessment->toArray()
        );
    }

    /**
     * Delete course assessment
     *
     * Soft delete a specific course assessment. The assessment must belong to the given course.
     *
     * @authenticated
     *
     * @urlParam course integer required The ID of the course. Example: 1
     * @urlParam assessment integer required The ID of the assessment. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Course Assessment deleted successfully"
     * }
     * @response 404 {
     *   "success": false,
     *   "message": "Assessment does not belong to this course."
     * }
     */
    public function destroy(Course $course, CourseAssessments $assessment)
    {
        if ((int) $assessment->course_id !== (int) $course->id) {
            return $this->error('Assessment does not belong to this course.', 404);
        }
        $assessment->delete();

        return $this->deleted('Course Assessment deleted successfully');
    }
}
