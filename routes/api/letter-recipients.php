<?php

use App\Http\Controllers\LetterRecipientController;

Route::post('letters/multi-recipient', [LetterRecipientController::class, 'store'])->middleware('permission:create multi-recipient letters');
