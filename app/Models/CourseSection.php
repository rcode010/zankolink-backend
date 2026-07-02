<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; 
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany; 

class CourseSection extends Model
{
    use SoftDeletes; 

    protected $fillable = ['course_id', 'teacher_id', 'title'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(SectionItem::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(SectionSubmission::class);
    }
}