<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LetterResource;
use App\Models\Letter;
use App\Models\LetterFlow;
use App\Services\LetterActionService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Letter Workflow
 *
 * APIs for LetterWorkFlow.
 */
class LetterWorkflowController extends Controller
{
    use ApiResponses;

    /**
     * Approve a letter and log the activity.
     */
    public function approve(Letter $letter, Request $request, LetterActionService $letterActionService)
    {
        $this->authorize('approve', $letter);
        $user = $request->user();

        if ($letter->status !== 'pending' || $letter->is_executed()) {
            return $this->error('This letter has already been processed.', 422);
        }

        DB::transaction(function () use ($letter, $user, $request, $letterActionService) {
            $letter->update([
                'status' => 'approved',
            ]);

            $oldReceiverId = $letter->receiver_id;

            LetterFlow::create([
                'letter_id' => $letter->id,
                'action' => 'approved',
                'actor_id' => $user->id,
                'from_recipient_id' => $oldReceiverId,
                'to_recipient_id' => null,
                'note' => $request->input('note', 'Letter approved successfully.'),
            ]);
            $letter->refresh();
            $letterActionService->execute($letter);
        });

        return $this->ok('Letter approved successfully.',
            (new LetterResource($letter
                ->fresh()
                ->load(
                    'sender:id,name',
                    'receiver:id,name',
                    'attachments'
                )))
                ->resolve()
        );
    }

    /**
     * Decline a letter and log the activity.
     */
    public function decline(Letter $letter, Request $request)
    {
        $this->authorize('decline', $letter);
        $user = $request->user();

        if ($letter->status !== 'pending') {
            return $this->error('This letter has already been processed.', 422);
        }

        DB::transaction(function () use ($letter, $user, $request) {
            $letter->update([
                'status' => 'rejected',
            ]);

            $oldReceiverId = $letter->receiver_id;

            LetterFlow::create([
                'letter_id' => $letter->id,
                'action' => 'rejected',
                'actor_id' => $user->id,
                'from_recipient_id' => $oldReceiverId,
                'to_recipient_id' => null,
                'note' => $request->input('note', 'Letter declined by user.'),
            ]);
        });

        $letter->refresh();

        return $this->ok('Letter declined successfully.',
            (new LetterResource($letter
                ->fresh()
                ->load(
                    'sender:id,name',
                    'receiver:id,name',
                    'attachments'
                )))
                ->resolve()
        );
    }

    /**
     * Forward a letter and log the from/to recipients.
     */
    public function forward(Letter $letter, Request $request)
    {
        $this->authorize('forward', $letter);
        $user = $request->user();

        $request->validate([
            'receiver_id' => 'required|exists:users,id',
        ]);

        if ($letter->receiver_id === (int) $request->receiver_id) {
            return $this->error("New receiver can't be the same as current one.", 400);
        }

        $oldReceiverId = $letter->receiver_id;

        DB::transaction(function () use ($letter, $user, $request, $oldReceiverId) {

            $letter->update([
                'sender_id' => $user->id,
                'receiver_id' => $request->receiver_id,
                'status' => 'pending',
            ]);

            LetterFlow::create([
                'letter_id' => $letter->id,
                'action' => 'forwarded',
                'actor_id' => $user->id,
                'from_recipient_id' => $oldReceiverId,
                'to_recipient_id' => $request->receiver_id,
                'note' => $request->input('note', 'Letter forwarded successfully.'),
            ]);
        });

        $letter->refresh();

        return $this->ok('Letter forwarded successfully.',
            (new LetterResource($letter
                ->fresh()
                ->load(
                    'sender:id,name',
                    'receiver:id,name',
                    'attachments'
                )))
                ->resolve()
        );
    }
}
