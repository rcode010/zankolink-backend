<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLetterBroadcastRequest;
use App\Models\LetterBroadcast;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class LetterBroadcastController extends Controller
{
    use ApiResponses;

    public function index(StoreLetterBroadcastRequest $request){
        $letterBroadcasts = LetterBroadcast::query()
            ->where('is_active', true)
            ->latest('published_at')->get();
        return $this->ok("LetterBroadcasts fetched",$letterBroadcasts->toArray());
    }

    public function store(Request $request){

    }
}
