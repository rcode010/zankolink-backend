<?php

use App\Http\Controllers\Api\StudentCourseController;

Route::get('/courses/{course}/students', [StudentCourseController::class, 'courseStudents'])
    ->middleware('can:view students,course');
