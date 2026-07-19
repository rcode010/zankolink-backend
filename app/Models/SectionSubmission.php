<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SectionSubmission extends Model
{
    use HasFactory;

    protected $fillable = ['course_section_id', 'course_assessment_id', 'description','created_by_teacher_id'];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'deadline' => 'datetime',

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

    public function courseAssessment(): BelongsTo
    {
        return $this->belongsTo(CourseAssessments::class, 'course_assessment_id');
    }
    public function creator()
    {
        return $this->belongsTo(
            Teacher::class,
            'created_by_teacher_id'
        );
    }
}
