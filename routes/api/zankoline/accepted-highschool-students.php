<?php

use App\Http\Controllers\AcceptedHighschoolStudentsController;

Route::get('/registrar/students', [AcceptedHighschoolStudentsController::class, 'index']);
Route::get('/registrar/departments', [AcceptedHighschoolStudentsController::class, 'departments']);
Route::get('/registrar/students/analytics', [AcceptedHighschoolStudentsController::class, 'statistics']);
Route::patch('/registrar/student/{highSchoolStudent}/email', [AcceptedHighschoolStudentsController::class, 'assignEmail']);
