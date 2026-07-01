<?php

use App\Http\Controllers\Api\FacultyController;

Route::prefix('faculties')->group(function () {
    Route::get('/', [FacultyController::class, 'index'])->middleware('permission:view faculties');
    Route::post('/', [FacultyController::class, 'store'])->middleware('permission:create faculties');
    Route::get('/{faculty}', [FacultyController::class, 'show'])->middleware('permission:view faculties');
    Route::patch('/{faculty}', [FacultyController::class, 'update'])->middleware('permission:update faculties');
    Route::delete('/{faculty}', [FacultyController::class, 'destroy'])->middleware('permission:delete faculties');
});
