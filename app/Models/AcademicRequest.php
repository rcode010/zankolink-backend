<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicRequest extends Model
{
    protected $fillable = [
        'type',
        'subject',
        'description',
        'user_id',
        'status',
        'department_id',
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
}
