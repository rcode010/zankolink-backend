<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $fillable = [
        'year',
        'start_date',
        'end_date',
        'semester',
        'is_active',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
 
    /*
     * "At most one active year" is enforced only in ActivateAcademicYearService,
     * not here. Activate a year through that service — writing
     * $year->update(['is_active' => true]) (or ->save()) directly on this
     * model will NOT deactivate the others.
     */
}
