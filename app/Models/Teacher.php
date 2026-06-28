<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'title', 'speciality'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'teacher_department');
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_teacher')
            ->withPivot('role')
            ->withTimestamps();
    }
}
