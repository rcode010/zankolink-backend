<?php

use App\Http\Controllers\Api\MoodleStudentCourseController;
use App\Http\Controllers\CalendarEventsController;
use Illuminate\Support\Facades\Route;

Route::prefix('moodle')->group(function () {
    Route::get('/calendar-events',[CalendarEventsController::class,'myAssignments']);
    Route::get('/my-courses', [MoodleStudentCourseController::class, 'myCourses']);
    Route::get('/my-courses/{course}', [MoodleStudentCourseController::class, 'showCourse']);
    Route::get('/my-courses/{course}/sections', [MoodleStudentCourseController::class, 'sections']);
});
