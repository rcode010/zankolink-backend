<?php

namespace App\Http\Controllers;

use App\Http\Requests\GlobalSearchRequest;
use App\Http\Resources\SearchResource;
use App\Models\AcademicRequest;
use App\Models\Course;
use App\Models\Letter;
use App\Traits\ApiResponses;
use Illuminate\Database\Eloquent\Builder;

class GlobalSearchController extends Controller
{
    use ApiResponses;

    public function search(GlobalSearchRequest $request)
    {

        $keyword = $request->query('keyword');

        $results = [
            'courses' => Course::search($keyword)
                ->query(fn (Builder $query) => $query->with('academicYear'))
                ->get(),
            'academicRequests' => AcademicRequest::search($keyword)->get(),
            'letters' => Letter::search($keyword)->get(),
        ];

        return $this->ok('Retrieved successfully', (new SearchResource($results))->resolve());
    }
}
