<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Letter;
use App\Models\LetterFlow;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LetterWorkflowController extends Controller
{
    use ApiResponses;

    /**
     * Approve a letter and log the activity.
     */
    public function approve(Letter $letter, Request $request)
    {
        $user = $request->user();

        if ($letter->status !== 'pending') {
            return $this->error('This letter has already been processed.', 422);
        }


        DB::transaction(function () use ($letter, $user, $request, $activeScope) {
            $letter->update([
                'status' => 'approved',
            ]);

            LetterFlow::create([
                'letter_id'  => $letter->id,
                'action'     => 'approved',
                'actor_id'   => $user->id,
                'role'       => $activeScope?->role?->name,
                'from_recipient_id' => null,
                'to_recipient_id'   => null,
                'note'       => $request->input('note', 'Letter approved successfully.'),
            ]);
        });

        $letter->refresh();
        return $this->ok('Letter approved successfully and workflow logged.', $letter->toArray());
    }

    /**
     * Decline a letter and log the activity.
     */
    public function decline(Letter $letter, Request $request)
    {
        $user = $request->user();

        if ($letter->status !== 'pending') {
            return $this->error('This letter has already been processed.', 422);
        }


        DB::transaction(function () use ($letter, $user, $request, $activeScope) {
            $letter->update([
                'status' => 'rejected',
            ]);

            LetterFlow::create([
                'letter_id'  => $letter->id,
                'action'     => 'rejected',
                'actor_id'   => $user->id,
                'role'       => $activeScope?->role?->name,
                'from_recipient_id' => null,
                'to_recipient_id'   => null,
                'note'       => $request->input('note', 'Letter declined by user.'),
            ]);
        });

        $letter->refresh();
        return $this->ok('Letter declined successfully and workflow logged.', $letter->toArray());
    }

    /**
     * Forward a letter and log the from/to recipients.
     */
    public function forward(Letter $letter, Request $request)
    {
        $user = $request->user();

        $request->validate([
            'receiver_id' => 'required|exists:users,id',
        ]);

        if ($letter->receiver_id === (int) $request->receiver_id) {
            return $this->error("New receiver can't be the same as current one.", 400);
        }

        $oldReceiverId = $letter->receiver_id; 

        DB::transaction(function () use ($letter, $user, $request, $activeScope, $oldReceiverId) {
           
            $letter->update([
                'sender_id'   => $user->id,
                'receiver_id' => $request->receiver_id,
                'status'      => 'pending', 
            ]);

            
            LetterFlow::create([
                'letter_id'          => $letter->id,
                'action'             => 'forwarded',
                'actor_id'           => $user->id,
                'role'               => $activeScope?->role?->name,
                'from_recipient_id'  => $oldReceiverId,          
                'to_recipient_id'    => $request->receiver_id,  
                'note'               => $request->input('note', 'Letter forwarded successfully.'),
            ]);
        });

        $letter->refresh();
        return $this->ok('Letter forwarded successfully and workflow logged.', $letter->toArray());
    }

    /**
     * Raise a letter and log the from/to recipients.
     */
    public function raise(Letter $letter, Request $request)
    {
        $user = $request->user();

        $request->validate([
            'receiver_id' => 'required|exists:users,id',
        ]);

        if ($letter->receiver_id === (int) $request->receiver_id) {
            return $this->error("New receiver can't be the same as current one.", 400);
        }

        $oldReceiverId = $letter->receiver_id;

        DB::transaction(function () use ($letter, $user, $request, $activeScope, $oldReceiverId) {
           
            $letter->update([
                'sender_id'   => $user->id,
                'receiver_id' => $request->receiver_id,
                'status'      => 'pending',
            ]);

            LetterFlow::create([
                'letter_id'          => $letter->id,
                'action'             => 'raised',
                'actor_id'           => $user->id,
                'role'               => $activeScope?->role?->name,
                'from_recipient_id'  => $oldReceiverId,          
                'to_recipient_id'    => $request->receiver_id,   
                'note'               => $request->input('note', 'Letter raised to a higher level.'),
            ]);
        });

        $letter->refresh();
        return $this->ok('Letter raised successfully and workflow logged.', $letter->toArray());
    }
}
