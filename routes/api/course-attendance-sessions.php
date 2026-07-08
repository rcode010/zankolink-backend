<?php

use App\Http\Controllers\Api\CourseAttendanceSessionsController;

Route::prefix('moodle/attendance-sessions')->group(function () {
    Route::post('/', [CourseAttendanceSessionsController::class, 'store']);
});
