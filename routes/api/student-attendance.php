<?php

use App\Http\Controllers\Api\StudentAttendanceController;

Route::prefix('moodle/attendance-sessions')->group(function () {
    Route::post('/{session}/attendance', [StudentAttendanceController::class, 'store'])->middleware('permission:create attendance records');
    Route::get('/{session}/attendance', [StudentAttendanceController::class, 'getAttendance'])->middleware('permission:view attendance records');
    Route::patch('/{session}/student/{student}', [StudentAttendanceController::class, 'updateStudentAttendance'])->middleware('permission:update attendance records');
});

Route::get('moodle/my-attendance', [StudentAttendanceController::class, 'myAttendance'])->middleware('permission:view own attendance records');
