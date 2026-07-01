<?php

use App\Http\Controllers\Api\DepartmentController;

Route::prefix('departments')->group(function () {
    Route::get('/', [DepartmentController::class, 'index'])->middleware('permission:view departments');
    Route::post('/', [DepartmentController::class, 'store'])->middleware('permission:create departments');
    Route::get('/{department}', [DepartmentController::class, 'show'])->middleware('permission:view departments');
    Route::patch('/{department}', [DepartmentController::class, 'update'])->middleware('permission:update departments');
    Route::delete('/{department}', [DepartmentController::class, 'destroy'])->middleware('permission:delete departments');
    Route::patch('/{department}/seat', [DepartmentController::class, 'updateSeat'])->middleware('permission:update department seats');
});
