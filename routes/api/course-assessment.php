<?php

use App\Http\Controllers\CourseAssesmentsController;

Route::prefix('moodle')->group(function () {
    Route::get('/courses/{course}/assessments', [CourseAssesmentsController::class, 'index']);
    Route::post('/courses/{course}/assessments', [CourseAssesmentsController::class, 'store']);
    Route::get('/courses/{course}/assessments/{assessment}', [CourseAssesmentsController::class, 'show']);
    Route::delete('/courses/{course}/assessments/{assessment}', [CourseAssesmentsController::class, 'destroy']);
    Route::patch('/courses/{course}/assessments/bulk', [CourseAssesmentsController::class, 'bulkUpdate']);
    Route::patch('/courses/{course}/assessments/{assessment}', [CourseAssesmentsController::class, 'update']);
});
