<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSelection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Student-Selection
 *
 * APIs for student-selection CRUD.
 */
class StudentSelectionController extends Controller
{
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
    public function saveCourseSelection(Request $request)
    {
        // 1. Get authenticated user and student profile
        $user = auth()->user();
        $student = $user->student;

        if (! $student) {
            return response()->json(['message' => 'Student record not found.'], 404);
        }

        $studentId = $student->id;
        $studentStage = (int) $student->stage;
        $departmentId = $student->department_id;

       // Check if course selection is open
        $department = $student->department;

        if (
            ! $department->course_selection_starts_at ||
            ! $department->course_selection_ends_at ||
            now()->lt($department->course_selection_starts_at) ||
            now()->gte($department->course_selection_ends_at)
        ) {
            return response()->json([
                'message' => 'Course selection is currently closed.'
            ], 403);
        }

        // 2. Validate academic year
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'distinct|exists:courses,id',
        ]);

        $academicYearId = $request->academic_year_id;

        // 3. Fetch all active courses available for this specific department and stage
        $availableCourses = Course::where('department_id', $departmentId)
            ->where('year_level', $studentStage)
            ->where('is_active', true)
            ->get();

        // -----------------------------------------------------------------
        // CASE 1: Stage 1 Students (Auto-enroll all mandatory courses)
        // -----------------------------------------------------------------
        if ($studentStage === 1) {
            $mandatoryCourseIds = $availableCourses->where('type', 'mandatory')->pluck('id')->toArray();

            if (empty($mandatoryCourseIds)) {
                return response()->json(['message' => 'No mandatory courses found for Stage 1.'], 422);
            }

            DB::transaction(function () use ($studentId, $academicYearId, $mandatoryCourseIds) {
                DB::table('course_student')
                    ->where('student_id', $studentId)
                    ->where('academic_year_id', $academicYearId)
                    ->delete();

                $enrollData = [];
                foreach ($mandatoryCourseIds as $courseId) {
                    $enrollData[] = [
                        'student_id' => $studentId,
                        'course_id' => $courseId,
                        'academic_year_id' => $academicYearId,
                        'enrolled_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table('course_student')->insert($enrollData);
            });

            return response()->json(['message' => 'Stage 1 student successfully auto-enrolled in all mandatory courses.'], 200);
        }

        // -----------------------------------------------------------------
        // CASE 2: Other Stages (Stage 2, 3, 4 - Manual selection with seat limit)
        // -----------------------------------------------------------------
        if (! $request->has('course_ids') || empty($request->course_ids)) {
            return response()->json(['message' => 'The course ids field is required for this stage.'], 422);
        }

        $selectedCourseIds = $request->course_ids;

        $hasApprovedSelection = CourseSelection::where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'approved')
            ->exists();

        if ($hasApprovedSelection) {
            return response()->json([
                'message' => 'Your course selection for this academic year has already been approved and can no longer be changed here. Please contact your department.',
            ], 422);
        }

        $courses = Course::whereIn('id', $selectedCourseIds)
            ->where('department_id', $departmentId)
            ->where('year_level', $studentStage)
            ->where('is_active', true)
            ->get();

        if ($courses->count() !== count($selectedCourseIds)) {
            $invalidCourseIds = array_values(array_diff($selectedCourseIds, $courses->pluck('id')->toArray()));

            return response()->json([
                'message' => 'One or more selected courses are not available for your department or stage.',
                'invalid_course_ids' => $invalidCourseIds,
            ], 422);
        }

        $electiveCourse = $courses->where('type', 'elective')->first();
        $electiveCount = $courses->where('type', 'elective')->count();

        if ($electiveCount > 1) {
            return response()->json(['message' => 'You cannot select more than one elective course.'], 422);
        }

        try {
            DB::transaction(function () use ($studentId, $academicYearId, $courses, $electiveCourse) {
                if ($electiveCourse && ! is_null($electiveCourse->seats)) {
                    $lockedElective = Course::where('id', $electiveCourse->id)
                        ->lockForUpdate()
                        ->first();

                    $takenSeats = CourseSelection::where('course_id', $lockedElective->id)
                        ->where('academic_year_id', $academicYearId)
                        ->where('student_id', '!=', $studentId)
                        ->whereIn('status', ['pending', 'approved'])
                        ->count();

                    if ($takenSeats >= $lockedElective->seats) {
                        throw new \RuntimeException("SEAT_FULL:{$lockedElective->name}");
                    }
                }

                CourseSelection::where('student_id', $studentId)
                    ->where('academic_year_id', $academicYearId)
                    ->where('status', '!=', 'approved')
                    ->delete();

                $selectionData = [];
                foreach ($courses as $course) {
                    $selectionData[] = [
                        'student_id' => $studentId,
                        'course_id' => $course->id,
                        'academic_year_id' => $academicYearId,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                CourseSelection::insert($selectionData);
            });
        } catch (\RuntimeException $e) {
            if (str_starts_with($e->getMessage(), 'SEAT_FULL:')) {
                $courseName = substr($e->getMessage(), strlen('SEAT_FULL:'));

                return response()->json([
                    'message' => "Sorry, the elective course ({$courseName}) is full!",
                ], 422);
            }

            throw $e;
        }

        return response()->json(['message' => 'Course selection saved successfully and is pending department approval.'], 200);
    }
}