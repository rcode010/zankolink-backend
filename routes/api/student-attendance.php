<?php

use App\Http\Controllers\Api\StudentAttendanceController;

Route::prefix('moodle/attendance-sessions')->group(function () {
    Route::post('/{session}/attendance', [StudentAttendanceController::class, 'store']);
    Route::get('/{session}/attendance', [StudentAttendanceController::class, 'getAttendance']);
});

Route::get('moodle/my-attendance', [StudentAttendanceController::class, 'myAttendance']);
