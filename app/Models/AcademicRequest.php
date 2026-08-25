<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

class AcademicRequest extends Model
{
    use Searchable,Searchable;
    public function searchableAs(): string
    {
        return 'academic_requests_index';
    }
    public function toSearchableArray()
    {
        return [
            'subject' => $this->subject,
        ];
    }
    protected $fillable = [
        'type',
        'subject',
        'description',
        'user_id',
        'status',
        'department_id',
        'academic_year_id',
        'file_name',
        'file_type',
        'file_size',
        'file_path',
    ];

    public function attachments()
    {
        return $this->hasMany(AcademicRequestAttachments::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }}
