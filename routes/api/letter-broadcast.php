<?php


use App\Http\Controllers\LetterBroadcastController;

Route::get('/letter-broadcast',[LetterBroadcastController::class,'index']);
Route::get('/letter-broadcast/{letterBroadcast}',[LetterBroadcastController::class,'show']);
Route::post('/letter-broadcast',[LetterBroadcastController::class,'store']);
