<?php

namespace App\Policies;

use App\Models\Letter;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LetterPolicy
{
    /**
     * Determine whether the user can view any letters.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view letters');
    }

    public function viewBroadcast(User $user): bool
    {
        return $user->hasRole('MINISTRY_ADMIN');
    }
    /**
     * Determine whether the user can view a specific letter.
     */
    public function view(User $user, Letter $letter): bool
    {
        if ($user->hasRole('MINISTRY_ADMIN')) {
            return true;
        }

        if ((int) $letter->sender_id === (int) $user->id) {
            return true;
        }

        if ((int) $letter->receiver_id === (int) $user->id) {
            return true;
        }

        if ((int) $letter->original_sender_id === (int) $user->id) {
            return true;
        }

        return DB::table('letter_flow')
            ->where('letter_id', $letter->id)
            ->where(function ($query) use ($user) {
                $query->where('actor_id', $user->id)
                    ->orWhere('from_recipient_id', $user->id)
                    ->orWhere('to_recipient_id', $user->id);
            })
            ->exists();
    }

    /**
     * Determine whether the user can create letters.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create letters');
    }

    /**
     * Determine whether the user can update a letter.
     */
    public function update(User $user, Letter $letter): bool
    {
        if (! $user->hasPermissionTo('update letters')) {
            return false;
        }

        if ($user->hasRole('MINISTRY_ADMIN')) {
            return true;
        }

        return $letter->status === 'pending'
            && (
                (int) $letter->sender_id === (int) $user->id
                || (int) $letter->original_sender_id === (int) $user->id
                || (int) $letter->receiver_id === (int) $user->id
            );
    }
    public function sign(User $user, Letter $letter): bool
    {
        if (! $user->hasPermissionTo('create signatures')) {
            return false;
        }

        return $letter->status === 'pending'
            && (int) $letter->receiver_id === (int) $user->id;
    }


    /**
     * Determine whether the user can raise/sign a letter.
     */
    public function raise(User $user, Letter $letter): bool
    {
        if (! $user->hasPermissionTo('raise letters')) {
            return false;
        }

        return $letter->status === 'pending'
            && (int) $letter->receiver_id === (int) $user->id;
    }

    /**
     * Determine whether the user can approve a letter.
     */
    public function approve(User $user, Letter $letter): bool
    {
        if (! $user->hasPermissionTo('approve letters')) {
            return false;
        }

        return $letter->status === 'pending'
            && (int) $letter->receiver_id === (int) $user->id;
    }

    /**
     * Determine whether the user can decline a letter.
     */
    public function decline(User $user, Letter $letter): bool
    {
        if (! $user->hasPermissionTo('decline letters')) {
            return false;
        }

        return $letter->status === 'pending'
            && (int) $letter->receiver_id === (int) $user->id;
    }

    /**
     * Determine whether the user can forward a letter.
     */
    public function forward(User $user, Letter $letter): bool
    {
        if (! $user->hasPermissionTo('forward letters')) {
            return false;
        }

        return $letter->status === 'pending'
            && (int) $letter->receiver_id === (int) $user->id;
    }

    /**
     * Determine whether the user can stamp a letter.
     */
    public function stamp(User $user, Letter $letter): bool
    {
        if (! $user->hasPermissionTo('create stamps')) {
            return false;
        }
        return $letter->status === 'pending'
            && (int) $letter->receiver_id === (int) $user->id;
    }

    /**
     * Determine whether the user can delete a letter.
     */
    public function delete(User $user, Letter $letter): bool
    {
        return $user->hasRole('MINISTRY_ADMIN');
    }

    public function restore(User $user, Letter $letter): bool
    {
        return false;
    }

    public function forceDelete(User $user, Letter $letter): bool
    {
        return false;
    }
}
