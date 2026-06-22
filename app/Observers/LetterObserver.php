<?php

namespace App\Observers;

use App\Models\Letter;

class LetterObserver
{
    /**
     * Handle the Letter "created" event.
     */
    public function creating(Letter $letter)
    {
        // Get the max existing number.
        // Since it's a string now, we cast it to integer for the math.
        $latest = Letter::max('letter_number');

        // Increment
        $next = $latest ? (int) $latest + 1 : 1;

        // Assign as string
        $letter->letter_number = (string) $next;
    }

    /**
     * Handle the Letter "updated" event.
     */
    public function updated(Letter $letter): void
    {
        //
    }

    /**
     * Handle the Letter "deleted" event.
     */
    public function deleted(Letter $letter): void
    {
        //
    }

    /**
     * Handle the Letter "restored" event.
     */
    public function restored(Letter $letter): void
    {
        //
    }

    /**
     * Handle the Letter "force deleted" event.
     */
    public function forceDeleted(Letter $letter): void
    {
        //
    }
}
