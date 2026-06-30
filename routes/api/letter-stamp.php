<?php


use App\Http\Controllers\LetterStampController;

Route::post('/stamp', [LetterStampController::class, 'store']);
