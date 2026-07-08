<?php

use App\Http\Controllers\Api\CourseAttendanceSessionsController;

Route::prefix('moodle/attendance-sessions')->group(function () {
    Route::get('/', [CourseAttendanceSessionsController::class, 'index']);
    Route::post('/', [CourseAttendanceSessionsController::class, 'store']);
    Route::get('/{session}', [CourseAttendanceSessionsController::class, 'show']);
});
