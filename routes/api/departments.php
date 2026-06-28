<?php

use App\Http\Controllers\Api\DepartmentController;

Route::prefix('departments')->group(function () {
    Route::get('/', [DepartmentController::class, 'index']);
    Route::post('/', [DepartmentController::class, 'store']);
    Route::get('/{department}', [DepartmentController::class, 'show']);
    Route::patch('/{department}', [DepartmentController::class, 'update']);
    Route::delete('/{department}', [DepartmentController::class, 'destroy']);
    Route::patch('/{department}/seat', [DepartmentController::class, 'updateSeat']);
});
