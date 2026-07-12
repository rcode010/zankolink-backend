<?php

use App\Http\Controllers\AcademicRequestController;

Route::prefix('moodle')->group(function () {
    Route::get('/academic-requests',[AcademicRequestController::class,'index'])->middleware('permission:view academic requests');
    Route::get('/academic-requests/{academicRequest}',[AcademicRequestController::class,'show'])->middleware('permission:view academic request');
    Route::post('/academic-requests', [AcademicRequestController::class, 'store'])->middleware('permission:create academic requests');
});
