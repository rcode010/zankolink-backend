<?php


use App\Http\Controllers\LetterBroadcastController;

Route::get('/letter-broadcast',[LetterBroadcastController::class,'index']);
Route::post('/letter-broadcast',[LetterBroadcastController::class,'store']);
