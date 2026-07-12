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
            'title' => $this->material_file_name,
            'type' => $this->resolveType(),
            'url' => $this->resolveUrl(),
            'content' => $this->resolveContent(),
            'size' => $this->resolveHumanReadableSize(),
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

        if ($this->material_file_type === 'note') {
            return 'note';
        }

        return strtoupper($this->material_file_type);
    }

    protected function resolveContent(): ?string
    {
        if ($this->material_file_type !== 'note') {
            return null;
        }

        return $this->material_file_url;
    }

    /**
     * External links are stored as full URLs already.
     * Uploaded files are stored as a raw storage path, so we resolve
     * them to a full public URL here for the API response.
     */
    protected function resolveUrl(): ?string
    {
        if ($this->material_file_type === 'note') {
            return null;
        }

        if ($this->material_file_type === 'link' || Str::startsWith($this->material_file_url, ['http://', 'https://'])) {
            return $this->material_file_url;
        }

        return Storage::disk('public')->url($this->material_file_url);
    }

    protected function resolveHumanReadableSize(): ?string
    {
        if ($this->material_file_type === 'link' || $this->material_file_type === 'note') {
            return null;
        }

        if (Str::startsWith($this->material_file_url, ['http://', 'https://'])) {
            return null;
        }

        if (! Storage::disk('public')->exists($this->material_file_url)) {
            return null;
        }

        return $this->formatBytes(Storage::disk('public')->size($this->material_file_url));
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return sprintf('%s %s', number_format($bytes, $index === 0 ? 0 : 1, '.', ''), $units[$index]);
    }
}
