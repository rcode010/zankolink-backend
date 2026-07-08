<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseAttendanceSessionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'session_date' => $this->session_date,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'course' => new CourseResource($this->whenLoaded('course')),
            'teacher' => new TeacherResource($this->whenLoaded('teacher')),
            'created_at' => $this->created_at,
        ];
    }
}
