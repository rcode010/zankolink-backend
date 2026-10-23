<?php

use App\Http\Controllers\RejectedStudentsController;

Route::get('/process/rejected-students', [RejectedStudentsController::class, 'index']);
