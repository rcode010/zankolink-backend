<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseAssessmentsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'course' => $this->whenLoaded('course', function () {
                return [
                    'id' => $this->course->id,
                    'name' => $this->course->name,
                ];
            }),

            'teacher' => $this->whenLoaded('teacher', function () {
                return [
                  'id' => $this->teacher->id,
                  'name' => $this->teacher->user->name,
                  'user_id' => $this->teacher->user->id
                ];
            }),

            'academic_year' => $this->whenLoaded('academicYear'),

            'title' => $this->title,
            'max_mark' => $this->max_mark,
            'weight' => $this->weight,
            'due_at' => $this->due_at,
            'is_published' => $this->is_published,


            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),

        ];
    }
}
