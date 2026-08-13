<?php


use App\Http\Controllers\AcceptedHighschoolStudentsController;

Route::get("/highschool-students/department",[AcceptedHighschoolStudentsController::class,'show']);
