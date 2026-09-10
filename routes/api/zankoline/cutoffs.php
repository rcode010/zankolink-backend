<?php

use App\Http\Controllers\CutoffsController;

Route::get('/zankoline/cutoffs', [CutoffsController::class, 'index']);
