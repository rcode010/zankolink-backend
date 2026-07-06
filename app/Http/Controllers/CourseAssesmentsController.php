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

class CourseAssesmentsController extends Controller
{
    use ApiResponses;

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

    public function update(UpdateCourseAssessmentRequest $request, Course $course, CourseAssessments $assessment)
    {
        if ((int) $assessment->course_id !== (int) $course->id) {
            return $this->error('Assessment does not belong to this course.', 404);
        }
        $credentials = $request->validated();

        $assessment->update($credentials);

        return $this->ok('Course Assessment updated successfully', $assessment->toArray());

    }

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

    public function destroy(Course $course, CourseAssessments $assessment)
    {
        if ((int) $assessment->course_id !== (int) $course->id) {
            return $this->error('Assessment does not belong to this course.', 404);
        }
        $assessment->delete();

        return $this->deleted('Course Assessment deleted successfully');
    }
}
