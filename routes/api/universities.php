<?php

use App\Http\Controllers\Api\UniversityController;

Route::prefix('universities')->group(function () {
    Route::get('/', [UniversityController::class, 'index'])->middleware('permission:view universities');
    Route::post('/', [UniversityController::class, 'store'])->middleware('permission:create universities');
    Route::get('/{university}', [UniversityController::class, 'show'])->middleware('permission:view university');
    Route::patch('/{university}', [UniversityController::class, 'update'])->middleware('permission:update universities');
    Route::delete('/{university}', [UniversityController::class, 'destroy'])->middleware('permission:delete universities');
});
