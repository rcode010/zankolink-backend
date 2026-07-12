<?php

use App\Http\Controllers\Api\MoodleStudentCourseController;
use Illuminate\Support\Facades\Route;

Route::prefix('moodle')->group(function () {
    Route::get('/my-courses', [MoodleStudentCourseController::class, 'myCourses']);
    Route::get('/my-courses/{course}', [MoodleStudentCourseController::class, 'showCourse']);
    Route::get('/my-courses/{course}/sections', [MoodleStudentCourseController::class, 'sections']);
});