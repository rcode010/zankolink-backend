<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Letter extends Model
{
    protected $fillable = [
        'title', 'body', 'type', 'receiver_type', 'receiver_id',
        'sender_type', 'sender_id', 'original_sender_id', 'status', 'academic_year'
    ];


}
