<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLetterBroadcastRequest;
use App\Models\LetterBroadcast;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
/**
 * @group Letter-Broadcast
 *
 * APIs for letter-broadcast creation.
 */
class LetterBroadcastController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $letterBroadcasts = LetterBroadcast::query()
            ->where('is_active', true)
            ->get();

        return $this->ok('LetterBroadcasts fetched', $letterBroadcasts->toArray());
    }

    public function show(LetterBroadcast $letterBroadcast)
    {
        return $this->ok('LetterBroadcast fetched', $letterBroadcast->toArray());
    }

    public function store(StoreLetterBroadcastRequest $request)
    {
        $credentials = $request->validated();

        $letterBroadcast = DB::transaction(function () use ($credentials,$request) {

            $letterBroadcast = LetterBroadcast::create([
                'title' => $credentials['title'],
                'body' => $credentials['body'],
            ]);
            $letterBroadcast = $letterBroadcast->fresh();
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store('attachments/letter-broadcasts', 'public');

                    $letterBroadcast->attachments()->create([
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                        'file_path' => $path,
                    ]);
                }
            }
            return $letterBroadcast;
        });

        return $this->created(
            'Letter broadcast created',
            $letterBroadcast->load('attachments')->toArray()
        );

    }
}
