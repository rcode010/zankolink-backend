<?php

use App\Http\Controllers\Api\MoodleStudentCourseController;
use App\Http\Controllers\CalendarEventsController;
use Illuminate\Support\Facades\Route;

Route::prefix('moodle')->group(function () {
    Route::get('/calendar-events', [CalendarEventsController::class, 'myAssignments'])->middleware('permission:view section submissions');
    Route::get('/my-courses', [MoodleStudentCourseController::class, 'myCourses'])->middleware('permission:view courses');
    Route::get('/my-courses/{course}', [MoodleStudentCourseController::class, 'showCourse'])->middleware('permission:view courses');
    Route::get('/my-courses/{course}/sections', [MoodleStudentCourseController::class, 'sections'])->middleware('permission:view course sections');
});
