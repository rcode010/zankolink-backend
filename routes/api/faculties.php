<?php

use App\Http\Controllers\Api\FacultyController;

Route::prefix('faculties')->group(function () {
    Route::get('/', [FacultyController::class, 'index']);
    Route::post('/', [FacultyController::class, 'store']);
    Route::get('/{faculty}', [FacultyController::class, 'show']);
    Route::patch('/{faculty}', [FacultyController::class, 'update']);
    Route::delete('/{faculty}', [FacultyController::class, 'destroy']);
});
