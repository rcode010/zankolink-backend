<?php

use App\Http\Controllers\Api\SectionSubmissionAttachmentController;
use App\Http\Controllers\Api\SectionSubmissionController;

Route::prefix('moodle')->group(function () {
   Route::get('/course-sections/{section}/submissions', [SectionSubmissionController::class, 'index']);
    Route::post('/course-sections/{section}/submissions', [SectionSubmissionController::class, 'store']);
    Route::get('/section-submissions/{submission}', [SectionSubmissionController::class, 'show']);
    Route::put('/section-submissions/{submission}', [SectionSubmissionController::class, 'update']);
    Route::delete('/section-submissions/{submission}', [SectionSubmissionController::class, 'destroy']);
    Route::get('/section-submission-attachments/{attachment}/download', [SectionSubmissionAttachmentController::class, 'download']);
    Route::delete('/section-submission-attachments/{attachment}', [SectionSubmissionAttachmentController::class, 'destroy']);

});
