<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentContactInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'phone',
        'email',
        'id_number',
        'governorate',
        'home_address',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    public function student()
    {
        return $this->belongsTo(HighSchoolStudent::class);
    }
}
