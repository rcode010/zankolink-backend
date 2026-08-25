<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'course' => CourseResource::collection($this->resource['courses']),
            'academicRequest' => AcademicRequestResource::collection($this->resource['academicRequests']),
            'letter' => LetterResource::collection($this->resource['letters']),
        ];
    }
}
