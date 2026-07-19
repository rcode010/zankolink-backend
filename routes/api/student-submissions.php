<?php

use App\Http\Controllers\Api\StudentSubmissionController;

Route::prefix('moodle')->group(function () {
    Route::post('/section-submissions/{submission}/submit', [StudentSubmissionController::class, 'store'])->middleware('permission:create student submissions');
    Route::get('/section-submissions/{submission}/student/{student}', [StudentSubmissionController::class, 'show'])->middleware('permission:view student submission');
    Route::delete('/student-submissions/{studentSubmission}', [StudentSubmissionController::class, 'destroy'])->middleware('permission:delete student submissions');
   Route::get('/section-submissions/{submission}/student-submissions', [StudentSubmissionController::class, 'index'])->middleware('permission:view student submissions');
});
