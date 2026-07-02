<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LetterBroadcastAttachements extends Model
{
    protected $fillable = ['letter_broadcast_id', 'file_type', 'file_size', 'file_path'];

   public function letterBroadcast(){
       return $this->belongsTo(LetterBroadcast::class);
   }
}
