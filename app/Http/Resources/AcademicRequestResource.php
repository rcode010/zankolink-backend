<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AcademicRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'subject' => $this->subject,
            'description' => $this->description,
            'status' => $this->status,
            'academic_year' => [
                'id' => $this->academic_year_id,
                'year' => $this->whenLoaded('academicYear', fn () => $this->academicYear->year)],
            'user' => [
                'id' => $this->user_id,
                'name' => $this->whenLoaded('user', fn () => $this->user->name),
            ],
            'department' => [
                'id' => $this->department_id,
                'name' => $this->whenLoaded('department', fn () => $this->department->name),
            ],
            'attachments' => $this->whenLoaded('attachments', fn () => $this->attachments->map(fn ($attachment) => [
                'id' => $attachment->id,
                'file_name' => $attachment->file_name,
                'file_type' => $attachment->file_type,
                'file_size' => $attachment->file_size,
                'file_url' => $attachment->file_path
                    ? Storage::disk('public')->url($attachment->file_path)
                    : null,
            ])
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
