<?php

use App\Http\Controllers\Api\SectionSubmissionController;

Route::get('/course-sections/{section}/submissions', [SectionSubmissionController::class, 'index']);
Route::post('/course-sections/{section}/submissions', [SectionSubmissionController::class, 'store']);
Route::get('/section-submissions/{submission}', [SectionSubmissionController::class, 'show']);
Route::put('/section-submissions/{submission}', [SectionSubmissionController::class, 'update']);
Route::delete('/section-submissions/{submission}',[SectionSubmissionController::class, 'destroy']);
