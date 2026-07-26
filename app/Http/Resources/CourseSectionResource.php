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
            'teacher' => $this->whenLoaded('teacher', function () use ($request) {
                $teacher = (new TeacherResource($this->teacher))->resolve($request);
                $teacher['role'] = $this->teacher_role;

                return $teacher;
            }),
            'items' => SectionItemResource::collection($this->whenLoaded('items')),
            'submissions' => SectionSubmissionResource::collection($this->whenLoaded('submissions')->load('creator', 'attachments', 'courseAssessment')),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
