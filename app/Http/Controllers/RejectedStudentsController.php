<?php

namespace App\Http\Controllers;

use App\Http\Resources\LetterResource;
use App\Models\HighSchoolStudent;
use App\Traits\ApiResponses;

class RejectedStudentsController extends Controller
{
    use ApiResponses;

    public function index()
    {
        $students = HighSchoolStudent::where('status', 'rejected')->get();

        return $this->ok('Rejected students returned successfully', $students->toArray());
    }
}
