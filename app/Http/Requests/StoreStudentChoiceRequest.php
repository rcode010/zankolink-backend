<?php

namespace App\Http\Requests;

use App\Models\AcademicYear;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentChoiceRequest extends FormRequest
{
    public function rules(): array
    {
        $student = $this->user();
        $activeYearId = AcademicYear::where('is_active', 1)->value('id');

        return [
            'choices' => [
                'required',
                'array',
                'min:1',
                'max:50',
            ],
            'choices.*.department_offering_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('department_offerings', 'id')
                    ->where(function ($query) use ($student, $activeYearId) {
                        $query->where('academic_year_id', $activeYearId);
                        if ($student->major_type === 'literary') {
                            $query->where('major_type', 'literary');
                        }
                    }),
            ],
            'choices.*.preference_order' => [
                'required',
                'integer',
                'min:1',
                'distinct',

            ],
            'choices.*.score' => [
                'required',
                'numeric',
                'decimal:0,3',
                'between:0,100',
            ],
            'choices.*.is_local' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
