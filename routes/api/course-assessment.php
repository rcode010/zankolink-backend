<?php

use App\Http\Controllers\CourseAssesmentsController;

Route::prefix('moodle')->group(function () {
    Route::get('/courses/{course}/assessments', [CourseAssesmentsController::class, 'index'])->middleware('permission:view course assessments');
    Route::post('/courses/{course}/assessments', [CourseAssesmentsController::class, 'store'])->middleware('permission:create course assessments');
    Route::get('/courses/{course}/assessments/{assessment}', [CourseAssesmentsController::class, 'show'])->middleware('permission:view course assessment');
    Route::delete('/courses/{course}/assessments/{assessment}', [CourseAssesmentsController::class, 'destroy'])->middleware('permission:delete course assessments');
    Route::patch('/courses/{course}/assessments/bulk', [CourseAssesmentsController::class, 'bulkUpdate'])->middleware('permission:update course assessments');
    Route::patch('/courses/{course}/assessments/{assessment}', [CourseAssesmentsController::class, 'update'])->middleware('permission:update course assessments');
});
