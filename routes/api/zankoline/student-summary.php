<?php

use App\Http\Controllers\HighSchoolStudentsController;

Route::middleware('ability:admin')->group(function () {
    Route::get('/zankoline/high-school-students/summary', [HighSchoolStudentsController::class, 'summary']);
});
