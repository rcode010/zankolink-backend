<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionSubmissionAttachment extends Model
{
    protected $fillable = ['section_submission_id', 'file_name', 'file_type', 'file_size', 'file_url'];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(SectionSubmission::class, 'section_submission_id');
    }
}
