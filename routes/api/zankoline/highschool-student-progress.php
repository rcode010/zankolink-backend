<?php

use App\Http\Controllers\HighSchoolStudentProgressController;

Route::post('/zankoline/highschool-student-progress', [HighSchoolStudentProgressController::class, 'update'])->middleware('auth');
