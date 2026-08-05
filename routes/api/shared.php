<?php

use App\Http\Controllers\AcademicRequestController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseMarkController;
use App\Http\Controllers\Api\StudentCourseController;
use App\Http\Controllers\Api\StudentSelectionController;
use App\Http\Controllers\StudentMarksController;
Route::post('/students/course-selection', [StudentSelectionController::class, 'saveCourseSelection'])->middleware('permission:create student course selections');

Route::get('/moodle/courses/{course}/gradebook', [StudentMarksController::class, 'gradeBook'])->middleware('permission:view gradebook');
Route::get('/students/available-courses', [StudentCourseController::class, 'availableCourses'])->middleware('permission:view courses');
Route::get('/moodle/courses/{course}/my-marks', [CourseMarkController::class, 'myMarks'])->middleware('permission:view own marks');
Route::patch('/moodle/academic-requests/{academicRequest}', [AcademicRequestController::class, 'update'])->middleware('permission:update academic request');
Route::get('/moodle/academic-requests/department', [AcademicRequestController::class, 'departmentAcademicRequests'])->middleware('permission:view academic requests');
Route::get('/courses/{course}/students', [StudentCourseController::class, 'courseStudents'])
    ->middleware('can:view students,course');

Route::get('/moodle/courses/{course}/teachers', [CourseController::class, 'getTeachers']);
