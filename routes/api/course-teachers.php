<?php

use App\Http\Controllers\Api\CourseTeacherController;

Route::prefix('courses/{course}')->group(function () {
    Route::post('/assign-teacher', [CourseTeacherController::class, 'store'])->middleware('permission:assign course teachers');
    Route::get('/teachers', [CourseTeacherController::class, 'courseTeachers'])->middleware('permission:view course teachers');
    Route::put('/teachers/{teacher}', [CourseTeacherController::class, 'update'])->middleware('permission:update course teachers');
    Route::delete('/teachers/{teacher}', [CourseTeacherController::class, 'destroy'])->middleware('permission:delete course teachers');
});
