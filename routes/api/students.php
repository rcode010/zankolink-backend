<?php

use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\StudentCourseController;
use App\Http\Controllers\Api\StudentSelectionController;

Route::prefix('students')->group(function () {
    Route::get('/', [StudentController::class, 'index'])->middleware('permission:view students');
    Route::post('/', [StudentController::class, 'store'])->middleware('permission:create students');
    Route::get('/{student}', [StudentController::class, 'show'])->middleware('permission:view students');
    Route::patch('/{student}', [StudentController::class, 'update'])->middleware('permission:update students');
    Route::delete('/{student}', [StudentController::class, 'destroy'])->middleware('permission:delete students');
    Route::post('/course-selection', [StudentSelectionController::class, 'saveCourseSelection'])->middleware('permission:create student course selections');
});
