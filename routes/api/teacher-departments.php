<?php

use App\Http\Controllers\Api\TeacherDepartmentController;

Route::prefix('departments/{department}')->group(function () {
    Route::post('/assign-teacher', [TeacherDepartmentController::class, 'store']);
    Route::get('/teachers', [TeacherDepartmentController::class, 'index']);
    Route::delete('/teachers/{teacher}', [TeacherDepartmentController::class, 'destroy']);
});
