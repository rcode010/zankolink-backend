<?php
use \App\Http\Controllers\StudentSubjectsController;

Route::get('/zankoline/student-subjects/student', [StudentSubjectsController::class, 'getStudentSubjects']);