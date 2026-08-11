<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentQuotasRequest;
use App\Models\DepartmentOfferingQuotas;
use App\Traits\ApiResponses;

class DepartmentQuotasController extends Controller
{
    use ApiResponses;

    public function index() {}

    public function store(DepartmentQuotasRequest $request)
    {
        $credentials = $request->validated();

        $quota = DepartmentOfferingQuotas::UpdateOrCreate(
            [
                'department_offering_id' => $credentials['department_offering_id'],
                'locality_type' => $credentials['locality_type'],
            ],
            [
                'capacity' => $credentials['capacity'],
            ]
        );

        return $this->ok('Department quota updated successfully!', $quota->toArray());
    }
}
