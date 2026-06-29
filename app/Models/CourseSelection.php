<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseSelection extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'student_id',
        'academic_year_id',
        'status',
    ];
}
