<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use softDeletes;

    protected $fillable = ['user_id', 'title', 'speciality'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
