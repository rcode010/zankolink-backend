<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFacultyRequest;
use App\Http\Requests\UpdateFacultyRequest;
use App\Http\Resources\FacultyResource;
use App\Models\Faculty;
use App\Models\University;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class FacultyController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $faculties = QueryBuilder::for(Faculty::class)
            ->with('university:id,name')
            ->allowedFilters(
                'name',
                'is_active',
                'university_id'
            )
            ->latest()
            ->paginate($request->query('per_page', 15));

        return $this->ok(
            'Faculties retrieved successfully',
            FacultyResource::collection($faculties)
                ->response()
                ->getData(true)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFacultyRequest $request, University $university)
    {
        $faculty = $university->faculties()->create(
            $request->validated()
        );

        return $this->success(
            'Faculty created successfully.',
            (new FacultyResource($faculty))
                ->toArray($request),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Faculty $faculty)
    {
        $faculty->load('university:id,name');

        return $this->ok(
            'Faculty retrieved successfully',
            (new FacultyResource($faculty))
                ->toArray(request())
        );

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFacultyRequest $request, Faculty $faculty)
    {
        $faculty->update(
            $request->validated()
        );

        return $this->ok(
            'Faculty updated successfully.',
            (new FacultyResource(
                $faculty->fresh()->load('university:id,name')
            ))->toArray($request)
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Faculty $faculty)
    {
        $faculty->delete();

        return $this->ok(
            'Faculty deleted successfully.',
        );
    }
}
