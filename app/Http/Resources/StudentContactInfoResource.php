<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentContactInfoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,

            'phone' => $this->phone,
            'email' => $this->email,
            'id_number' => $this->id_number,

            'governorate' => $this->governorate,
            'home_address' => $this->home_address,

            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
