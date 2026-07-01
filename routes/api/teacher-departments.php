<?php

use App\Http\Controllers\Api\TeacherDepartmentController;

Route::prefix('departments/{department}')->group(function () {
    Route::post('/assign-teacher', [TeacherDepartmentController::class, 'store'])->middleware('permission:assign teachers');
    Route::get('/teachers', [TeacherDepartmentController::class, 'index'])->middleware('permission:view teachers');
    Route::delete('/teachers/{teacher}', [TeacherDepartmentController::class, 'destroy'])->middleware('permission:delete teachers');
});
