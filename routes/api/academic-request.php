<?php

use App\Http\Controllers\AcademicRequestController;

Route::prefix('moodle')->group(function () {
    Route::get('/academic-requests',[AcademicRequestController::class,'index']);
    Route::get('/academic-requests/{academicRequest}',[AcademicRequestController::class,'show']);
    Route::post('/academic-requests', [AcademicRequestController::class, 'store']);
});
