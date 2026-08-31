<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateHighSchoolStudentProgressRequest;
use App\Traits\ApiResponses;

class HighSchoolStudentProgressController extends Controller
{
    use ApiResponses;

    public function index() {}

    public function update(UpdateHighSchoolStudentProgressRequest $request)
    {
        $credentials = $request->validated();

        $highSchoolStudent = $request->user();
        $highSchoolStudent->current_step = $credentials['step_path'];
        $highSchoolStudent->save();

        return $this->ok('Progress updated successfully');
    }
}
