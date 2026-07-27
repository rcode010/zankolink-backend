<?php

use App\Http\Controllers\AcademicRequestController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\StudentCourseController;

Route::get('/moodle/academic-requests/department', [AcademicRequestController::class, 'departmentAcademicRequests'])->middleware('permission:view academic requests');
Route::get('/courses/{course}/students', [StudentCourseController::class, 'courseStudents'])
    ->middleware('can:view students,course');

Route::get('/moodle/courses/{course}/teachers', [CourseController::class, 'getTeachers']);
