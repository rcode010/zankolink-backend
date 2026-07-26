<?php

use App\Http\Controllers\Api\LecturerCourseController;
use Illuminate\Support\Facades\Route;

Route::prefix('moodle/lecturer')->group(function () {
    Route::get('/courses', [LecturerCourseController::class, 'courses'])->middleware('permission:view courses');
    Route::get('/courses/{course}', [LecturerCourseController::class, 'showCourse'])->middleware('permission:view courses');
    Route::get('/courses/{course}/submissions-summary', [LecturerCourseController::class, 'submissionsSummary'])->middleware('permission:view section submissions');
});
