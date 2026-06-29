<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Letter extends Model
{
    use HasFactory;

    protected $fillable = [
        'letter_number', 'title', 'body', 'type', 'receiver_id', 'sender_id',
        'original_sender_id', 'status', 'academic_year', 'verification_hash',
    ];

    // Relationship to the person who sent the letter
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // Updated relationship to the person who receives the letter
    public function receiver()
    {
        // We tell Laravel to use 'receiver_id' as the foreign key
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function originalSender()
    {
        return $this->belongsTo(User::class, 'original_sender_id');
    }

    public function attachments()
    {

        return $this->hasMany(Attachment::class);

    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
