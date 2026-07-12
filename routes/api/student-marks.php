<?php

use App\Http\Controllers\Api\CourseMarkController;
use App\Http\Controllers\StudentMarksController;

Route::prefix('moodle')->group(function () {
    Route::get('/course-assessments/{assessment}/marks', [StudentMarksController::class, 'AllAssessmentMarks'])->middleware('permission:view assessments marks');
    Route::post('/course-assessments/{assessment}/marks/bulk', [StudentMarksController::class, 'store'])->middleware('permission:create student marks');
    Route::get('/student-marks/{mark}', [StudentMarksController::class, 'show'])->middleware('permission:view student mark');
    Route::patch('/student-marks/{mark}', [StudentMarksController::class, 'update'])->middleware('permission:update student mark');

    Route::get('/courses/{course}/my-marks', [CourseMarkController::class, 'myMarks'])->middleware('permission:view own marks');
});
