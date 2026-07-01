<?php

use App\Http\Controllers\Api\CourseController;

Route::prefix('courses')->group(function () {
    Route::get('/', [CourseController::class, 'index'])->middleware('permission:view courses');
    Route::post('/', [CourseController::class, 'store'])->middleware('permission:create courses');
    Route::get('/{course}', [CourseController::class, 'show'])->middleware('permission:view courses');
    Route::patch('/{course}', [CourseController::class, 'update'])->middleware('permission:update courses');
    Route::delete('/{course}', [CourseController::class, 'destroy'])->middleware('permission:delete courses');
});
