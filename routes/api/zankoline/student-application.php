<?php

use App\Http\Controllers\StudentApplicationController;

Route::post('/zankoline/student-application', [StudentApplicationController::class, 'store']);
