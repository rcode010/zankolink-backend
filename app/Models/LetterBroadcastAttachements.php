<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class LetterBroadcastAttachements extends Model
{
    protected $fillable = ['letter_broadcast_id', 'file_type', 'file_size', 'file_name','file_path'];

   public function letterBroadcast(){
       return $this->belongsTo(LetterBroadcast::class);
   }

    protected $appends = ['file_url'];
    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path
            ? asset(Storage::disk('public')->url($this->file_path))
            : null;
    }
}
