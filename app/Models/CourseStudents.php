<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseStudents extends Model
{
    protected $table = 'course_student';

    public function student(){
        return $this->belongsTo(Student::class);
    }
    public function course(){
        return $this->belongsTo(Course::class);
    }
}
