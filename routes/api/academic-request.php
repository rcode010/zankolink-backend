<?php

use App\Http\Controllers\AcademicRequestController;

Route::prefix('moodle')->group(function () {
    Route::post('/academic-requests', [AcademicRequestController::class, 'store']);
});
