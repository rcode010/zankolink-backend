<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subjects extends Model
{
    protected $fillable = [
        'name',
        'major_type',
        'credit_number',
        'year_level',
    ];

}
