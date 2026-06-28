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
}
