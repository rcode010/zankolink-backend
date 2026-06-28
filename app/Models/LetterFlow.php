<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetterFlow extends Model
{
    use HasFactory;

    protected $table = 'letter_flow';

    /**
     * Disable mass assignment protection entirely for this model.
     * This will solve the MassAssignmentException once and for all.
     */
    protected $guarded = [];

    public function letter(): BelongsTo
    {
        return $this->belongsTo(Letter::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}