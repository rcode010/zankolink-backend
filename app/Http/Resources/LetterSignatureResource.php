<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LetterSignatureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'letter_id'         => $this->letter_id,
            'user_id'           => $this->user_id,
            'role_at_time'      => $this->role_at_time,
            'comment'           => $this->comment,
            'verification_hash' => $this->verification_hash,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
            
            
            'letter'            => $this->whenLoaded('letter'),
            'user'              => $this->whenLoaded('user'),
        ];
    }
}