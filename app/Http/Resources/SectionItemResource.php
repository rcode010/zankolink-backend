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
            'material_file_type' => $this->material_file_type,
            'material_file_name' => $this->material_file_name,
            'material_file_url' => $this->resolveUrl(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

    /**
     * External links are stored as full URLs already.
     * Uploaded files are stored as a raw storage path, so we resolve
     * them to a full public URL here for the API response.
     */
    protected function resolveUrl(): string
    {
        if (Str::startsWith($this->material_file_url, ['http://', 'https://'])) {
            return $this->material_file_url;
        }

        return Storage::disk('public')->url($this->material_file_url);
    }
}