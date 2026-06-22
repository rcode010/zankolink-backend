<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class University extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'admin_id', 'academic_year', 'location', 'start_date', 'end_date', 'established_year', 'is_active'];

    public function faculties(): HasMany
    {
        return $this->hasMany(Faculty::class);
    }
}
