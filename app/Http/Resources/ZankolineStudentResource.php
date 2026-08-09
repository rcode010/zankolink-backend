<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZankolineStudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'major_type' => $this->major_type,
            'gender' => $this->gender,
            'is_active' => $this->is_active,
            'status' => $this->status,

            'grade_average' => $this->grade_average,
            'grade_10' => $this->grade_10,
            'grade_11' => $this->grade_11,

            'accepted_department_offering_id' => $this->accepted_department_offering_id,
            'contacts' => StudentContactInfoResource::make(
                $this->whenLoaded('contacts')
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
