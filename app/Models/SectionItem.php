<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id',
        'title',
        'created_by_teacher_id',
        'description',
        'material_file_type',
        'material_file_name',
        'material_file_url',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'section_id');
    }

    public function creator()
    {
        return $this->belongsTo(
            Teacher::class,
            'created_by_teacher_id'
        );
    }
}
