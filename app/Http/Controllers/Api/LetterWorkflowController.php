<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Letter;
use App\Models\LetterFlow;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LetterWorkflowController extends Controller
{
    use ApiResponses;

    /**
     * Approve a letter and log the activity.
     *
     * @return JsonResponse
     */
    public function approve(Letter $letter, Request $request)
    {
        $user = $request->user();

        // 1. Authorization: Prevent users with the 'lecturer' role from approving letters
        if ($user->hasRole('lecturer')) {
            return $this->error('Lecturers are not authorized to approve letters.', 403);
        }

        // 2. Update letter status to approved
        $letter->update([
            'status' => 'approved',
        ]);

        // 3. Get the primary scope role if available for logging
        $activeScope = $user->userScopes()->first();

        // 4. Log the action in the letter_flow table
        LetterFlow::create([
            'letter_id' => $letter->id,
            'action' => 'approved',
            'actor_id' => $user->id,
            'role' => $activeScope ? $activeScope->role?->name : null,
            'scope_id' => $activeScope?->scope_id,
            'scope_type' => $activeScope?->scope_type,
            'note' => $request->input('note', 'Letter approved successfully.'),
        ]);

        // TODO: In the next step (Action Mapping), handle automatic triggers based on letter type

        return $this->ok('Letter approved successfully and workflow logged.', $letter->toArray());
    }

    /**
     * Decline a letter and log the activity.
     *
     * @return JsonResponse
     */
    public function decline(Letter $letter, Request $request)
    {
        $user = $request->user();

        // 1. Authorization: Prevent users with the 'lecturer' role from declining letters
        if ($user->hasRole('lecturer')) {
            return $this->error('Lecturers are not authorized to decline letters.', 403);
        }

        // 2. Update letter status to rejected
        $letter->update([
            'status' => 'rejected',
        ]);

        // 3. Get the primary scope role if available for logging
        $activeScope = $user->userScopes()->first();

        // 4. Log the action in the letter_flow table
        LetterFlow::create([
            'letter_id' => $letter->id,
            'action' => 'rejected',
            'actor_id' => $user->id,
            'role' => $activeScope ? $activeScope->role?->name : null,
            'scope_id' => $activeScope?->scope_id,
            'scope_type' => $activeScope?->scope_type,
            'note' => $request->input('note', 'Letter declined by user.'),
        ]);

        return $this->ok('Letter declined successfully and workflow logged.', $letter->toArray());
    }
}
