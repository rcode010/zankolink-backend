<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LetterBroadcast extends Model
{
    /** @use HasFactory<\Database\Factories\LetterBroadcastFactory> */
    use HasFactory;
    protected $fillable = ['title','body','is_active','published_at'];
}
