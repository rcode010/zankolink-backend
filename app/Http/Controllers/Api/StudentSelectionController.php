<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSelection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentSelectionController extends Controller
{
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

        // 2. Validate academic year
        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id',
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

        $courses = Course::whereIn('id', $selectedCourseIds)
            ->where('department_id', $departmentId)
            ->where('year_level', $studentStage)
            ->where('is_active', true)
            ->get();

        // Constraint check: Maximum of 1 elective course allowed
        $electiveCourse = $courses->where('type', 'elective')->first();
        $electiveCount = $courses->where('type', 'elective')->count();

        if ($electiveCount > 1) {
            return response()->json(['message' => 'You cannot select more than one elective course.'], 422);
        }

        // Constraint check: Verify seat capacity using CourseSelection Model (لادانی DB::table)
        if ($electiveCourse && ! is_null($electiveCourse->seats)) {
            $takenSeats = CourseSelection::where('course_id', $electiveCourse->id)
                ->where('academic_year_id', $academicYearId)
                ->whereIn('status', ['pending', 'approved'])
                ->count();

            if ($takenSeats >= $electiveCourse->seats) {
                return response()->json([
                    'message' => "Sorry, the elective course ({$electiveCourse->name}) is full!",
                ], 422);
            }
        }

        // Save temporary selection using CourseSelection Model
        DB::transaction(function () use ($studentId, $academicYearId, $courses) {
            CourseSelection::where('student_id', $studentId)
                ->where('academic_year_id', $academicYearId)
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

        return response()->json(['message' => 'Course selection saved successfully and is pending department approval.'], 200);
    }
}
