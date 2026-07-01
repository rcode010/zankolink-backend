<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetterStamp extends Model
{
    protected $fillable = ['letter_id', 'user_id', 'comment'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function letter(): BelongsTo
    {
        return $this->belongsTo(Letter::class);
    }
}
