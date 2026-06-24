<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUniversityRequest;
use App\Http\Requests\UpdateUniversityRequest;
use App\Http\Resources\UniversityResource;
use App\Models\University;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class UniversityController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $universities = QueryBuilder::for(University::class)
            ->allowedFilters(
                'name',
                'location',
            )
            ->latest()
            ->paginate(
                $request->query('per_page', 15)
            );

        return $this->ok(
            'Universities retrieved successfully.',
            UniversityResource::collection($universities)
                ->response()
                ->getData(true)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUniversityRequest $request)
    {
        $university = University::create(
            $request->validated()
        );

        return $this->success(
            'University created successfully.',
            (new UniversityResource($university))->toArray($request),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(University $university)
    {
        return $this->ok(
            'University retrieved successfully.',
            (new UniversityResource($university))
                ->toArray(request())
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUniversityRequest $request, University $university)
    {
        $university->update(
            $request->validated()
        );

        return $this->ok(
            'University updated successfully.',
            (new UniversityResource($university->fresh()))
                ->toArray($request),
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(University $university)
    {
        $university->delete();

        return $this->ok(
            'University deleted successfully.'
        );
    }
}
