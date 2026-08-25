<?php


use App\Http\Controllers\GlobalSearchController;

Route::get('/search',[GlobalSearchController::class,'search']);
