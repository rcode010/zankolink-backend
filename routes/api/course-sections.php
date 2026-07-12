<?php

use App\Http\Controllers\Api\CourseSectionController;
use Illuminate\Support\Facades\Route;


Route::prefix('moodle')->group(function () {

    // Course-scoped routes (Scoped under a specific parent course)
    Route::prefix('courses/{course}')->group(function () {
        Route::get('/sections', [CourseSectionController::class, 'index']);
        Route::post('/sections', [CourseSectionController::class, 'store']);
    });

    // Standalone routes for managing individual section resources
    Route::prefix('course-sections/{section}')->group(function () {
        Route::get('/', [CourseSectionController::class, 'show']);
        Route::put('/', [CourseSectionController::class, 'update']);
        Route::delete('/', [CourseSectionController::class, 'destroy']);
    });

});