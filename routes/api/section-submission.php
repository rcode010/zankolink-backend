<?php

use App\Http\Controllers\Api\SectionSubmissionController;

Route::post('/course-sections/{section}/submissions', [SectionSubmissionController::class, 'store']);
