<?php

use App\Http\Controllers\LetterVerificationController;

Route::get('/verify/{letter_uuid}', [LetterVerificationController::class, 'getLetterVerification'])->middleware('throttle:api');
