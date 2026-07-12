<?php

use App\Http\Controllers\Api\CourseSectionController;
use Illuminate\Support\Facades\Route;


Route::prefix('moodle')->group(function () {

    // Course-scoped routes (Scoped under a specific parent course)
    Route::prefix('courses/{course}')->group(function () {
        Route::get('/sections', [CourseSectionController::class, 'index'])->middleware('permission:view course sections');
        Route::post('/sections', [CourseSectionController::class, 'store'])->middleware('permission:create course sections');
    });

    // Standalone routes for managing individual section resources
    Route::prefix('course-sections/{section}')->group(function () {
        Route::get('/', [CourseSectionController::class, 'show'])->middleware('permission:view course section');
        Route::put('/', [CourseSectionController::class, 'update'])->middleware('permission:update course sections');
        Route::delete('/', [CourseSectionController::class, 'destroy'])->middleware('permission:delete course sections');
    });

});
