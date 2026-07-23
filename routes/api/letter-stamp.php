<?php

use App\Http\Controllers\LetterStampController;

Route::post('/stamps', [LetterStampController::class, 'store'])->middleware('permission:create stamps');
Route::get('/stamps', [LetterStampController::class, 'index'])->middleware('permission:view stamps');
