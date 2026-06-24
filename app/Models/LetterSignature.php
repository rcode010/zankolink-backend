<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LetterSignature extends Model
{
    protected $table = 'letter_signature';

    protected $fillable = [
        'letter_id',
        'user_id',
        'role_at_time',
        'comment',
        'verification_hash',
    ];

    public function letter()
    {
        return $this->belongsTo(Letter::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}