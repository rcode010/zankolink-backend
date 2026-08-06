<?php

use App\Http\Controllers\StudentContactInfoController;

Route::post('/zankoline/contact-info', [StudentContactInfoController::class, 'store']);
Route::put('/zankoline/contact-info', [StudentContactInfoController::class, 'update']);
