<?php

use App\Http\Controllers\DepartmentOfferingsController;

Route::middleware('ability:admin')->group(function () {
    Route::post('/zankoline/department-offering', [DepartmentOfferingsController::class, 'store']);
});

Route::middleware('ability:zankoline')->group(function () {
    Route::get('/zankoline/department-offering', [DepartmentOfferingsController::class, 'index']);
});
