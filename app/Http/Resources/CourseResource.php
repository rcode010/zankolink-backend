<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'credit_hours' => $this->credit_hours,
            'year_level' => $this->year_level,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'department_id' => $this->department_id,
            'color' => $this->color,
            'role' => $this->pivot?->role,
            'semester' => $this->semester,
            'students_count' => $this->whenCounted('students'),
            'sections_count' => $this->whenCounted('sections'),

            'department' => $this->whenLoaded('department', function () {
                return new DepartmentResource($this->department);
            }),
            'teachers' => $this->whenLoaded('teachers', function () {
                return TeacherResource::collection($this->teachers);
            }),
            'prerequisites' => $this->whenLoaded('prerequisites', function () {
                return $this->prerequisites->map(fn ($course) => [
                    'id' => $course->id,
                    'name' => $course->name,
                    'code' => $course->code,
                ]);
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
