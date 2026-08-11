<?php

use App\Http\Controllers\DepartmentQuotasController;

Route::post('/department-offering-quotas', [DepartmentQuotasController::class, 'store']);
