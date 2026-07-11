<?php

use App\Http\Controllers\Api\CourseMarkController;
use App\Http\Controllers\StudentMarksController;

Route::prefix('moodle')->group(function () {
    Route::get('/course-assessments/{assessment}/marks', [StudentMarksController::class, 'AllAssessmentMarks']);
    Route::post('/course-assessments/{assessment}/marks/bulk', [StudentMarksController::class, 'store']);
    Route::get('/student-marks/{mark}', [StudentMarksController::class, 'show']);
    Route::patch('/student-marks/{mark}', [StudentMarksController::class, 'update']);

    Route::get('/courses/{course}/my-marks', [CourseMarkController::class, 'myMarks']);
});
