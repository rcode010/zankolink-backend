<?php

use App\Http\Controllers\Api\LetterWorkflowController;
use Illuminate\Support\Facades\Route;

Route::prefix('letters/{letter}')->whereNumber('letter')->group(function () {
    Route::post('/approve', [LetterWorkflowController::class, 'approve'])->middleware('permission:approve letters');
    Route::post('/decline', [LetterWorkflowController::class, 'decline'])->middleware('permission:decline letters');
    Route::post('/forward', [LetterWorkflowController::class, 'forward'])->middleware('permission:forward letters');
});
