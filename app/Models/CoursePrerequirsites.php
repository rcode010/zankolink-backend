<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoursePrerequirsites extends Model
{
    protected $fillable = ['course_id','prerequisite_course_id'];

    public function course(){
        return $this->belongsTo(Course::class);
    }
}
