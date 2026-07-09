<?php

use App\Http\Controllers\AcademicRequestController;

Route::prefix('moodle')->group(function () {
    Route::get('/academic-requests',[AcademicRequestController::class,'index']);
    Route::post('/academic-requests', [AcademicRequestController::class, 'store']);
});
