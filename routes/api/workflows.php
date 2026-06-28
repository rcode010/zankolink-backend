<?php

use App\Http\Controllers\Api\LetterWorkflowController;
use Illuminate\Support\Facades\Route;

Route::post('/letters/{letter}/approve', [LetterWorkflowController::class, 'approve'])->whereNumber('letter');
Route::post('/letters/{letter}/decline', [LetterWorkflowController::class, 'decline'])->whereNumber('letter');
