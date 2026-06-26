<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    protected $fillable = [
        'letter_id',
        'file_name',
        'file_type',
        'file_size',
        'file_url',
    ];

    public function letter(): BelongsTo
    {
        return $this->belongsTo(Letter::class);
    }
}
