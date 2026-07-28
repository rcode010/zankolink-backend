<?php

use App\Http\Controllers\CourseAssessmentsController;

Route::prefix('moodle')->group(function () {
    Route::get('/courses/{course}/assessments', [CourseAssessmentsController::class, 'index'])->middleware('permission:view course assessments');
    Route::post('/courses/{course}/assessments', [CourseAssessmentsController::class, 'store'])->middleware('permission:create course assessments');
    Route::get('/courses/{course}/assessments/{assessment}', [CourseAssessmentsController::class, 'show'])->middleware('permission:view course assessment');
    Route::delete('/courses/{course}/assessments/{assessment}', [CourseAssessmentsController::class, 'destroy'])->middleware('permission:delete course assessments');
    Route::patch('/courses/{course}/assessments/sync', [CourseAssessmentsController::class, 'syncAssessments'])->middleware('permission:update course assessments');
    Route::patch('/courses/{course}/assessments/publish', [CourseAssessmentsController::class, 'publishAssessments'])->middleware('permission:publish course assessments');
    Route::patch('/courses/{course}/assessments/{assessment}', [CourseAssessmentsController::class, 'update'])->middleware('permission:update course assessments');
});
