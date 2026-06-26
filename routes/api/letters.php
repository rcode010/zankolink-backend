<?php

use App\Http\Controllers\Api\LetterController;

Route::post('/letters', [LetterController::class, 'store']);
Route::get('/letters', [LetterController::class, 'index']);
Route::get('/letters/recents', [LetterController::class, 'recentLetters']);
Route::get('/letters/{letter}', [LetterController::class, 'show'])->whereNumber('letter');
Route::patch('/letters/{letter}', [LetterController::class, 'update'])->whereNumber('letter');
Route::post('/letters/{letter}/raise', [LetterController::class, 'raiseLetter'])->whereNumber('letter');
