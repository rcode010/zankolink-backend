<?php

use App\Http\Controllers\DepartmentOfferingsController;

Route::middleware('ability:admin')->group(function () {
    Route::put('/zankoline/department-offering', [DepartmentOfferingsController::class, 'upsert']);
    Route::delete('/zankoline/department-offering/{departmentOffering}', [DepartmentOfferingsController::class, 'destroy']);
    Route::get('/department-offering', [DepartmentOfferingsController::class, 'show']);
});

Route::middleware('ability:zankoline')->group(function () {
    Route::get('/zankoline/department-offering', [DepartmentOfferingsController::class, 'index']);
});
