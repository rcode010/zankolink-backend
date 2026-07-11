<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,

            'course' => new CourseResource($this->whenLoaded('course')),
            'teacher' => $this->whenLoaded(
                'teacher',
                fn () => $this->teacher ? new TeacherResource($this->teacher) : null
            ),
            'items' => SectionItemResource::collection($this->whenLoaded('items')),
            'submissions' => SectionSubmissionResource::collection($this->whenLoaded('submissions')),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
