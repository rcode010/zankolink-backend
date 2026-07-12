<?php

use App\Http\Controllers\Api\LecturerCourseController;
use Illuminate\Support\Facades\Route;

Route::prefix('moodle/lecturer')->group(function () {
    Route::get('/courses', [LecturerCourseController::class, 'courses']);
    Route::get('/courses/{course}', [LecturerCourseController::class, 'showCourse']);
    Route::get('/courses/{course}/submissions-summary', [LecturerCourseController::class, 'submissionsSummary']);
});