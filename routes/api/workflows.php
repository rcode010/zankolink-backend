<?php

use App\Http\Controllers\Api\LetterWorkflowController;
use Illuminate\Support\Facades\Route;

Route::prefix('letters/{letter}')->whereNumber('letter')->group(function () {
    Route::post('/approve', [LetterWorkflowController::class, 'approve']);
    Route::post('/decline', [LetterWorkflowController::class, 'decline']);
    Route::post('/forward', [LetterWorkflowController::class, 'forward']);
    Route::post('/raise', [LetterWorkflowController::class, 'raise']);
});