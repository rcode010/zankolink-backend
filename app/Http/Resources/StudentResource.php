<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
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
            'enrollment_type' => $this->enrollment_type,
            'stage' => $this->stage,
            'student_number' => $this->student_number,
            'status' => $this->status,

            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email'=>$this->user?->email,
            ],

            'department' => new DepartmentResource(
                $this->whenLoaded('department')
            ),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
