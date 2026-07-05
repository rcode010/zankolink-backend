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
            'is_active' => $this->is_active,
            'department_id' => $this->department_id,

            'department' => new DepartmentResource($this->whenLoaded('department')),

            'teachers' => TeacherResource::collection($this->whenLoaded('teachers')),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
