<?php

use App\Http\Controllers\Api\StudentSubmissionController;

Route::post('/section-submissions/{submission}/submit', [StudentSubmissionController::class, 'store']);
Route::get('/section-submissions/{submission}/my-submission', [StudentSubmissionController::class, 'mySubmission']);
