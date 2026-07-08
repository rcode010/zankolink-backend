<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentAttendanceResource extends JsonResource
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
            'attendance_session_id' => $this->attendance_session_id,
            'student' => new StudentResource($this->whenLoaded('student')),
            'status' => $this->status,
            'note' => $this->note,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
