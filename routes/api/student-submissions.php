<?php

use App\Http\Controllers\Api\StudentSubmissionController;

Route::prefix('moodle')->group(function () {
    Route::post('/section-submissions/{submission}/submit', [StudentSubmissionController::class, 'store'])->middleware('permission:create student submissions');
    Route::get('/section-submissions/{submission}/my-submission', [StudentSubmissionController::class, 'mySubmission'])->middleware('permission:view own submission');
    Route::get('/student-submissions/{studentSubmission}/download', [StudentSubmissionController::class, 'download'])->middleware('permission:download student submissions');
    Route::delete('/student-submissions/{studentSubmission}', [StudentSubmissionController::class, 'destroy'])->middleware('permission:delete student submissions');

    // --- Lecturer-facing: submission review & grading ---
   Route::get('/section-submissions/{submission}/student-submissions', [StudentSubmissionController::class, 'index'])->middleware('permission:view student submissions');
    Route::get('/section-submissions/{submission}/student/{student}', [StudentSubmissionController::class, 'show'])->middleware('permission:view student submission');
});
