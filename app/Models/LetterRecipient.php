<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetterRecipient extends Model
{
    protected $fillable = ['letter_id', 'recipient_id'];

    public function letter(): BelongsTo
    {
        return $this->belongsTo(Letter::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
