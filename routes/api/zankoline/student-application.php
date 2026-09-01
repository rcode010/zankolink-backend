<?php

use App\Http\Controllers\StudentApplicationController;

Route::post('/zankoline/student-application', [StudentApplicationController::class, 'store']);
Route::get('/zankoline/student-application', [StudentApplicationController::class, 'index']);

Route::get('/zankoline/student-application/status', [StudentApplicationController::class, 'show']);
