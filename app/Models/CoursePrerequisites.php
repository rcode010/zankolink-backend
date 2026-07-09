<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoursePrerequisites extends Model
{
    protected $fillable = ['course_id','prerequisite_course_id'];

    public function course(){
        return $this->belongsTo(Course::class);
    }
}
