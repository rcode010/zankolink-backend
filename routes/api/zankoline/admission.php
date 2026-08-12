<?php

use App\Http\Controllers\AdmissionRunController;

Route::post('/admissions/run', [AdmissionRunController::class, 'store']);
Route::get('/admissions/run', [AdmissionRunController::class, 'show']);
