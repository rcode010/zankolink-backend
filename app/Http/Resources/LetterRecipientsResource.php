<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class LetterRecipientsResource extends JsonResource
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

            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,

            'academic_year_id' => $this->academic_year_id,
            'is_archived' => (bool) $this->is_archived,

            'qr_code_path' => $this->qr_code_path,
            'qr_code_url' => $this->qr_code_path
                ? Storage::disk('public')->url($this->qr_code_path)
                : null,
            'verification_page'=>$this->verificationUrl,


            'recipients' => $this->whenLoaded('recipients', function () {
                return $this->recipients->map(function ($recipient) {
                    return [
                        'recipient_id' => $recipient->recipient_id,
                        'recipient_name' => $recipient->recipient->name,
                    ];
                });
            }),

            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

        ];
    }
}
