<?php


use App\Http\Controllers\LetterBroadcastController;

Route::get('/letter-broadcast',[LetterBroadcastController::class,'index']);
