<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSubmission extends Model
{
    public function submission(): BelongsTo
    {
        return $this->belongsTo(SectionSubmission::class, 'submission_id');
    }
}
