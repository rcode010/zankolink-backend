<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentOfferingSubjects extends Model
{
    protected $fillable = [
        'department_offering_id',
        'subject_id',
        'credit',
        'minimum_grade',
    ];
}
