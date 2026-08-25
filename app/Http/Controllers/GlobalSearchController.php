<?php

namespace App\Http\Controllers;

use App\Http\Requests\GlobalSearchRequest;
use App\Http\Resources\SearchResource;
use App\Models\AcademicRequest;
use App\Models\Course;
use App\Models\Letter;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    use ApiResponses;
    public function search(GlobalSearchRequest $request)
    {

        $keyword = $request->query('keyword');

        $results = [
            'courses' => Course::search($keyword)->get(),
            'academicRequests' => AcademicRequest::search($keyword)->get(),
            'letters' => Letter::search($keyword)->get(),
        ];
        return $this->ok("Retrieved successfully",(new SearchResource($results))->resolve());
    }

}
