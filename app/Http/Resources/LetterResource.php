<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LetterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'letter_number' => $this->letter_number,

            'original_sender_id' => $this->original_sender_id,
            'sender_id' => $this->sender_id,
            'receiver_id' => $this->receiver_id,

            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,

            'is_read' => (bool) $this->is_read,
            'academic_year' => $this->academic_year,
            'is_archived' => (bool) $this->is_archived,
            'status' => $this->status,
            'verification_hash' => $this->verification_hash,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'sender' => $this->whenLoaded('sender', function () {
                return [
                    'id' => $this->sender->id,
                    'name' => $this->sender->name,
                ];
            }),

            'receiver' => $this->whenLoaded('receiver', function () {
                return [
                    'id' => $this->receiver->id,
                    'name' => $this->receiver->name,
                ];
            }),
        ];
    }
}
