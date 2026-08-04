<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentOffering extends Model
{
    use HasFactory;
    protected $fillable=['department_id','academic_year_id','track_type','governorate','major_type','city','minimum_grade'];
}
