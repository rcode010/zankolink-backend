<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepartmentOffering extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'academic_year_id',
        'zankoline_capacity',
        'parallel_capacity',
        'governorate',
        'capacity',
        'major_type',
        'city',
        'minimum_grade_zankoline',
        'minimum_grade_parallel'
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function students()
    {
        return $this->belongsToMany(
            HighSchoolStudent::class,
            'student_choice',
            'department_offering_id',
            'student_id'
        )
            ->withPivot('priority', 'status')
            ->withTimestamps();
    }

    public function scopeLiteraryOnly(Builder $query): Builder
    {
        return $query->where('major_type', 'literary');
    }
}
