<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Letter extends Model
{
    protected $fillable = [
        'title', 'body', 'type', 'receiver_type', 'receiver_id',
        'sender_type', 'sender_id', 'original_sender_id', 'status', 'academic_year',
    ];

    // Relationship to the person who sent the letter
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // Updated relationship to the person who receives the letter
    public function receiver()
    {
        // We tell Laravel to use 'receiver_id' as the foreign key
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
