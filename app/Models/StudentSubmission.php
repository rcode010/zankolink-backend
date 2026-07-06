<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSubmission extends Model
{

    protected $fillable = ['submission_id', 'student_id', 'file_name', 'file_type', 'file_size', 'file_url'];
    public function submission(): BelongsTo
    {
        return $this->belongsTo(SectionSubmission::class, 'submission_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
