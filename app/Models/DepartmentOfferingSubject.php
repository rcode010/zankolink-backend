<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentOfferingSubject extends Model
{
    protected $fillable = [
        'department_id',
        'subject_id',
        'credit',
        'minimum_grade',
    ];
}
