<?php

use App\Http\Controllers\Api\SignatureController;

Route::post('/signatures', [SignatureController::class, 'store'])->middleware('permission:create signatures');
Route::get('/signatures/{id}', [SignatureController::class, 'show'])->middleware('permission:view signatures');
Route::get('/signatures', [SignatureController::class, 'index'])->middleware('permission:view signatures');
