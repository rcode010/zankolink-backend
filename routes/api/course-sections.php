<?php

use App\Http\Controllers\Api\CourseSectionController;
use Illuminate\Support\Facades\Route;

// Course-scoped routes (Scoped under a specific course)
Route::prefix('courses/{course}')->group(function () {
    Route::get('/sections', [CourseSectionController::class, 'index']);
    Route::post('/sections', [CourseSectionController::class, 'store']);
});

// Standalone routes for managing individual sections
Route::get('course-sections/{section}', [CourseSectionController::class, 'show']);
Route::put('course-sections/{section}', [CourseSectionController::class, 'update']);
Route::delete('course-sections/{section}', [CourseSectionController::class, 'destroy']);
