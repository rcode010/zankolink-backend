<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentOfferingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'department_id' => $this->department_id,
            'academic_year_id' => $this->academic_year_id,

            'zankoline_capacity' => $this->zankoline_capacity,
            'parallel_capacity' => $this->parallel_capacity,
            'major_type' => $this->major_type,

            'governorate' => $this->governorate,
            'city' => $this->city,

            'minimum_grade_zankoline' => $this->minimum_grade_zankoline,
            'minimum_grade_parallel' => $this->minimum_grade_parallel,

            'department' => DepartmentSummaryResource::make(
                $this->whenLoaded('department')
            ),

            //            'quotas' => DepartmentOfferingQuotaResource::collection(
            //                $this->whenLoaded('quotas')
            //            ),

            //            'subjects' => DepartmentOfferingSubjectResource::collection(
            //                $this->whenLoaded('subjects')
            //            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
