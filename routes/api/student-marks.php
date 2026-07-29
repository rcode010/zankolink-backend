<?php

use App\Http\Controllers\Api\CourseMarkController;
use App\Http\Controllers\StudentMarksController;

Route::prefix('moodle')->group(function () {
    Route::get('/course-assessments/{assessment}/marks', [StudentMarksController::class, 'AllAssessmentMarks'])->middleware('permission:view assessment marks');
    Route::post('/course-assessments/{assessment}/marks/bulk', [StudentMarksController::class, 'store'])->middleware('permission:create student marks');
    Route::get('/student-marks/{mark}', [StudentMarksController::class, 'show'])->middleware('permission:view student mark');
    Route::patch('/student-marks/{mark}', [StudentMarksController::class, 'update'])->middleware('permission:update student mark');
    Route::put('/courses/{course}/gradebook/marks', [StudentMarksController::class, 'storeGradeBook'])->middleware('permission:store gradebook');
});
