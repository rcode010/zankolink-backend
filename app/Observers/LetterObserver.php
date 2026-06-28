<?php

namespace App\Observers;

use App\Models\Letter;
use Illuminate\Support\Str;

class LetterObserver
{
    /**
     * Handle the Letter "created" event.
     */
    public function creating(Letter $letter)
    {
        if (! $letter->letter_number) {
            $letter->letter_number = (string) Str::uuid();
        }
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
