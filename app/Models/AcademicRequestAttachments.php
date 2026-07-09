<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicRequestAttachments extends Model
{
    protected $fillable = [
        'academic_request_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
    ];

    public function academicRequest()
    {
        return $this->belongsTo(AcademicRequest::class);
    }

}
