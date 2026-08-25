<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Letter extends Model
{
    use HasFactory, Searchable;
    public function searchableAs(): string
    {
        return 'letters_index';
    }
    public function toSearchableArray()
    {
        return [
            'title' => $this->title,
            'letter_number' =>  $this->letter_number,
        ];
    }

    protected $fillable = [
        'letter_number', 'title', 'body', 'type', 'receiver_id', 'sender_id',
        'original_sender_id', 'status', 'academic_year_id', 'verification_hash',
        'letter_uuid', 'qr_code_path', 'payload', 'executed_at',
    ];

    protected $hidden = ['verification_hash'];

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

    public function is_processed(): bool
    {
        return in_array($this->status, ['approved', 'rejected']);
    }

    public function flows()
    {
        return $this->hasMany(LetterFlow::class)
            ->orderBy('created_at', 'asc');
    }

    public function latestFlow()
    {
        return $this->hasOne(LetterFlow::class)
            ->latestOfMany();
    }
    public function signatures()
    {
        return $this->hasMany(LetterSignature::class);
    }

    public function stamps()
    {
        return $this->hasMany(LetterStamp::class);
    }

    public function recipients()
    {
        return $this->hasMany(LetterRecipient::class);
    }

    public function is_executed(): bool
    {
        return $this->executed_at ? true : false;
    }

    protected function verificationUrl(): Attribute
    {
        return Attribute::get(
            fn () => config('app.frontend_url')
                .'/verify/letters/'
                .$this->letter_uuid
        );
    }
}
