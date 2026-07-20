<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class SectionSubmissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $studentSubmissions = $this->relationLoaded('studentSubmissions')
            ? $this->studentSubmissions
            : collect();

        $latestStudentSubmission = $studentSubmissions->first();

        return [
            'id' => $this->id,
            'description' => $this->description,

            'course_assessment' => $this->whenLoaded('courseAssessment'),
            'section' => $this->whenLoaded(
                'section',
                fn () => [
                    'id' => $this->section->id,
                    'title' => $this->section->title,
                ]
            ),
            'created_by' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->user?->name,
                ];
            }),
            'attachments' => $this->whenLoaded(
                'attachments',
                fn () => $this->attachments->map(fn ($attachment) => [
                    'id' => $attachment->id,
                    'file_name' => $attachment->file_name,
                    'file_type' => $attachment->file_type,
                    'file_size' => $attachment->file_size,
                    'file_url' => Storage::disk('public')->url($attachment->file_url),
                ])
            ),

            'my_submissions' => $this->whenLoaded(
                'studentSubmissions',
                fn () => StudentSubmissionResource::collection($this->studentSubmissions)->resolve()
            ),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
