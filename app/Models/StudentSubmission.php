<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSubmission extends Model
{
    protected $fillable = [
        'submission_id',
        'student_id',
        'file_name',
        'file_type',
        'file_size',
        'file_url',
        'grade',
        'feedback',
        'graded_at',
        'graded_by',
    ];

    protected function casts(): array
    {
        return [
            'grade' => 'decimal:2',
            'graded_at' => 'datetime',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(SectionSubmission::class, 'submission_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    
    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'graded_by');
    }

   
    public function isGraded(): bool
    {
        return ! is_null($this->graded_at);
    }
}