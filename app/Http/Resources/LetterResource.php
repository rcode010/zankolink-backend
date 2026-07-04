<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'academic_year_id' => $this->academic_year_id,
            'is_archived' => (bool) $this->is_archived,
            'status' => $this->status,
            'qr_code_path' => $this->qr_code_path,
            'qr_code_url' => $this->qr_code_path
                ? Storage::disk('public')->url($this->qr_code_path)
                : null,
            'payload' => $this->payload,

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
