<?php

use App\Http\Controllers\Api\TeacherController;

Route::prefix('teachers')->group(function () {
    Route::get('/', [TeacherController::class, 'index'])->middleware('permission:view teachers');
    Route::post('/', [TeacherController::class, 'store'])->middleware('permission:create teachers');
    Route::get('/{teacher}', [TeacherController::class, 'show'])->middleware('permission:view teachers');
    Route::patch('/{teacher}', [TeacherController::class, 'update'])->middleware('permission:update teachers');
    Route::delete('/{teacher}', [TeacherController::class, 'destroy'])->middleware('permission:delete teachers');
});
