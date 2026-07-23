<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetterFlow extends Model
{
    use HasFactory;

    protected $table = 'letter_flow';

    protected $fillable = [
        'letter_id',
        'action',
        'actor_id',
        'role',
        'scope_id',
        'scope_type',
        'from_recipient_id',
        'to_recipient_id',
        'note',
    ];

    public function letter(): BelongsTo
    {
        return $this->belongsTo(Letter::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function fromRecipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_recipient_id');
    }

    public function toRecipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_recipient_id');
    }
}
