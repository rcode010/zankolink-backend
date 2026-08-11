<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $fillable = [
        'year',
        'start_date',
        'end_date',
        'semester',
        'is_active',
        'zankoline_submission_starts_at',
        'zankoline_submission_ends_at',

    ];
}
