<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetterSignature extends Model
{
    use HasFactory;
    
    protected $table = 'letter_signature';

    protected $fillable = [
        'letter_id',
        'user_id',
        'role_at_time',
        'comment',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function letter(): BelongsTo
    {
        return $this->belongsTo(Letter::class);
    }
}
