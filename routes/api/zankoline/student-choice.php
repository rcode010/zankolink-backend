<?php

use App\Http\Controllers\StudentsChoiceController;

Route::post('/zankoline/student-choice', [StudentsChoiceController::class, 'store']);
