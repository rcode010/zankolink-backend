<?php

use App\Http\Controllers\Api\LetterController;

Route::post('/letters', [LetterController::class, 'store'])->middleware('permission:create letters');
Route::get('/letters', [LetterController::class, 'index'])->middleware('permission:view letters');
Route::get('/letters/recents', [LetterController::class, 'recentLetters'])->middleware('permission:view letters');
Route::get('/letters/{letter}', [LetterController::class, 'show'])->whereNumber('letter')->middleware('permission:view letters');
Route::patch('/letters/{letter}', [LetterController::class, 'update'])->whereNumber('letter')->middleware('permission:update letters');
Route::post('/letters/{letter}/raise', [LetterController::class, 'raiseLetter'])->whereNumber('letter')->middleware('permission:raise letters');

Route::get('letters/inbox', [LetterController::class, 'inbox'])->middleware('permission:view letters');
Route::get('letters/outbox', [LetterController::class, 'outbox'])->middleware('permission:view letters');
Route::get('letters/archived', [LetterController::class, 'archived'])->middleware('permission:view letters');
Route::get('letters/dispatched', [LetterController::class, 'dispatched'])->middleware('permission:view letters');
