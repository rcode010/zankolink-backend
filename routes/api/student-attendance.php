<?php

use App\Http\Controllers\Api\StudentAttendanceController;

Route::prefix('moodle/attendance-sessions')->group(function () {
    Route::post('/{session}/attendance', [StudentAttendanceController::class, 'store']);
    Route::get('/{session}/attendance', [StudentAttendanceController::class, 'getAttendance']);
    Route::patch('/{session}/student/{student}', [StudentAttendanceController::class, 'updateStudentAttendance']);
});

Route::get('moodle/my-attendance', [StudentAttendanceController::class, 'myAttendance']);
