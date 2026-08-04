<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentApplication extends Model
{
    protected $fillable = [
        'student_id',
        'academic_year_id',
        'status',
        'draft_choices',
        'submitted_at',
    ];
}
