<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SectionItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'section_id' => $this->section_id,
            'title' => $this->title,
            'description' => $this->description,
            'created_by_teacher_id' => $this->created_by_teacher_id,
            'type' => $this->resolveType(),
            'created_by' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->user?->name,
                    'user_id' => $this->creator->user?->id,
                ];
            }),
            'material_file_type' => $this->material_file_type,
            'material_file_name' => $this->material_file_name,
            'material_file_url' => $this->resolveUrl(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

    protected function resolveType(): string
    {
        if ($this->material_file_type === 'link') {
            return 'link';
        }

        if ($this->material_file_type === null) {
            return 'note';
        }

        return strtoupper($this->material_file_type);
    }

    /**
     * External links are stored as full URLs already.
     * Uploaded files are stored as a raw storage path, so we resolve
     * them to a full public URL here for the API response.
     */
    protected function resolveUrl(): ?string
    {
        if ($this->material_file_type === null) {
            return null;
        }

        if ($this->material_file_type === 'link' || Str::startsWith($this->material_file_url, ['http://', 'https://'])) {
            return $this->material_file_url;
        }

        return Storage::disk('public')->url($this->material_file_url);
    }
}
