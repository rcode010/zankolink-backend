<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SectionSubmission extends Model
{
    protected $fillable = ['course_section_id', 'title', 'description', 'deadline','weight'];

     protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'deadline' => 'datetime'

        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'course_section_id');
    }

    public function studentSubmissions(): HasMany
    {
        return $this->hasMany(StudentSubmission::class, 'submission_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SectionSubmissionAttachment::class);
    }

}
