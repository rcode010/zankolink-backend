<?php

use App\Http\Controllers\Api\SignatureController;

Route::post('/signatures', [SignatureController::class, 'store']);
Route::get('/signatures/{id}', [SignatureController::class, 'show']);
Route::get('/signatures', [SignatureController::class, 'index']);
