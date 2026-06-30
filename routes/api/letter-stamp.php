<?php


use App\Http\Controllers\LetterStampController;

Route::post('/stamps', [LetterStampController::class, 'store']);
Route::get('/stamps', [LetterStampController::class, 'index']);
