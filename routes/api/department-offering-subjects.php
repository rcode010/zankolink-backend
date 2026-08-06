<?php

use App\Http\Controllers\DepartmentOfferingSubjectsController;

Route::get('/zankoline/department-offering-subjects', [DepartmentOfferingSubjectsController::class, 'index']);
Route::post('/zankoline/department-offering-subjects', [DepartmentOfferingSubjectsController::class, 'store']);
Route::patch('/zankoline/department-offering-subjects/{departmentOfferingSubject}', [DepartmentOfferingSubjectsController::class, 'update']);
Route::delete('/zankoline/department-offering-subjects/{departmentOfferingSubject}',[DepartmentOfferingSubjectsController::class, 'destroy']);
