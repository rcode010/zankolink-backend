<?php

use App\Http\Controllers\Api\SectionSubmissionAttachmentController;
use App\Http\Controllers\Api\SectionSubmissionController;

Route::prefix('moodle')->group(function () {
    Route::get('/course-sections/{section}/submissions', [SectionSubmissionController::class, 'index'])->middleware('permission:view section submissions');
    Route::post('/course-sections/{section}/submissions', [SectionSubmissionController::class, 'store'])->middleware('permission:create section submissions');
    Route::get('/section-submissions/my-assignments', [SectionSubmissionController::class, 'myAssignments']);
    Route::get('/section-submissions/{submission}', [SectionSubmissionController::class, 'show'])->middleware('permission:view section submission');
    Route::put('/section-submissions/{submission}', [SectionSubmissionController::class, 'update'])->middleware('permission:update section submissions');
    Route::delete('/section-submissions/{submission}', [SectionSubmissionController::class, 'destroy'])->middleware('permission:delete section submissions');
    Route::delete('/section-submission-attachments/{attachment}', [SectionSubmissionAttachmentController::class, 'destroy'])->middleware('permission:delete section submission attachments');
});
