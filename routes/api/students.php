<?php

use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\StudentSelectionController;

Route::prefix('students')->group(function () {
    Route::get('/', [StudentController::class, 'index']);
    Route::post('/', [StudentController::class, 'store']);
    Route::get('/{student}', [StudentController::class, 'show']);
    Route::patch('/{student}', [StudentController::class, 'update']);
    Route::delete('/{student}', [StudentController::class, 'destroy']);
    Route::post('/course-selection', [StudentSelectionController::class, 'saveCourseSelection']);
});
