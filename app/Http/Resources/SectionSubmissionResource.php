<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'title' => $this->title,
            'description' => $this->description,
            'deadline' => $this->deadline,
            'weight' => $this->weight,

            'section' => $this->whenLoaded(
                'section',
                fn () => [
                    'id' => $this->section->id,
                    'title' => $this->section->title,
                ]
            ),

            'attachments' => $this->whenLoaded(
                'attachments',
                fn () => $this->attachments->map(fn ($attachment) => [
                    'id' => $attachment->id,
                    'file_name' => $attachment->file_name,
                    'file_type' => $attachment->file_type,
                    'file_size' => $attachment->file_size,
                    'file_url' => $attachment->file_url,
                ])
            ),

            'grade' => $latestStudentSubmission?->grade,
            'feedback' => $latestStudentSubmission?->feedback,
            'graded_at' => $latestStudentSubmission?->graded_at?->toDateTimeString(),

            'my_submissions' => $this->whenLoaded(
                'studentSubmissions',
                fn () => StudentSubmissionResource::collection($this->studentSubmissions)->resolve()
            ),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
