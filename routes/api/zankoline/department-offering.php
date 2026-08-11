<?php

use App\Http\Controllers\DepartmentOfferingsController;

Route::middleware('ability:admin')->group(function () {
    Route::post('/zankoline/department-offering', [DepartmentOfferingsController::class, 'store']);
    Route::patch('/zankoline/department-offering/{departmentOffering}', [DepartmentOfferingsController::class, 'update']);
    Route::delete('/zankoline/department-offering/{departmentOffering}', [DepartmentOfferingsController::class, 'destroy']);
    Route::get('/department-offering', [DepartmentOfferingsController::class, 'show']);
});

Route::middleware('ability:zankoline')->group(function () {
    Route::get('/zankoline/department-offering', [DepartmentOfferingsController::class, 'index']);
});
