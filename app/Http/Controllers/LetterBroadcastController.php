<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLetterBroadcastRequest;
use App\Models\LetterBroadcast;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class LetterBroadcastController extends Controller
{
    use ApiResponses;

    public function index(Request $request){
        $letterBroadcasts = LetterBroadcast::query()
            ->where('is_active', true)
            ->get();
        return $this->ok("LetterBroadcasts fetched",$letterBroadcasts->toArray());
    }

    public function store(StoreLetterBroadcastRequest $request){
        $credentials = $request->validated();
        $letterBroadcast = LetterBroadcast::create($credentials);

        return $this->created("LetterBroadcast created",$letterBroadcast->toArray());
    }
}
