<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentOffering extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'academic_year_id',
        'capacity',
        'track_type',
        'governorate',
        'major_type',
        'city',
        'minimum_grade',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function students()
    {
        return $this->belongsToMany(
            HighSchoolStudent::class,
            'students_choices',
            'department_offering_id',
            'student_id'
        )
            ->withPivot('priority', 'status')
            ->withTimestamps();
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function scopeLiteraryOnly(Builder $query): Builder
    {
        return $query->where('major_type', 'literary');
    }
}
