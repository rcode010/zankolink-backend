<?php

use App\Http\Controllers\HighSchoolStudentsController;

Route::get('/zankoline/high-school-students/summary', [HighSchoolStudentsController::class, 'summary']);
