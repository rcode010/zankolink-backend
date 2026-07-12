<?php

use App\Http\Controllers\Api\CourseAttendanceSessionsController;

Route::prefix('moodle/attendance-sessions')->group(function () {
    Route::get('/', [CourseAttendanceSessionsController::class, 'index'])->middleware('permission:view attendance sessions');
    Route::post('/', [CourseAttendanceSessionsController::class, 'store'])->middleware('permission:create attendance sessions');
    Route::get('/{session}', [CourseAttendanceSessionsController::class, 'show'])->middleware('permission:view attendance session');
    Route::patch('/{session}', [CourseAttendanceSessionsController::class, 'update'])->middleware('permission:update attendance sessions');
    Route::delete('/{session}', [CourseAttendanceSessionsController::class, 'destroy'])->middleware('permission:delete attendance sessions');
});
