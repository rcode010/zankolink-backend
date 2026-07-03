<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LetterBroadcastResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inbox_type' => 'letter_broadcast',
            'title' => $this->title,
            'body' => $this->body,
            'is_active' => (bool) $this->is_active,
            'attachments' => $this->whenLoaded('attachments'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
