<?php

use App\Http\Controllers\LetterRecipientController;

Route::post('letters/multi-recipient', [LetterRecipientController::class, 'store']);
