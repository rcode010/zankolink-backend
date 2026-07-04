<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUniversityRequest;
use App\Http\Requests\UpdateUniversityRequest;
use App\Http\Resources\UniversityResource;
use App\Models\University;
use App\Services\CreateUniversityStructureService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UniversityController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', University::class);
        $per_page = $request->query('per_page', 15);

        $universities = QueryBuilder::for(University::class)
            ->with('admin:id,name')
            ->allowedFilters(
                AllowedFilter::callback(
                    'search',
                    function ($query, $value) {
                        $query->where(function ($q) use ($value) {
                            $q->where('name', 'like', "%{$value}%")
                                ->orWhere('location', 'like', "%{$value}%");
                        });
                    }
                ),
                'is_active'

            )
            ->latest()
            ->paginate($per_page);

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
    public function store(StoreUniversityRequest $request, CreateUniversityStructureService $createUniversityService)
    {
        $this->authorize('create', University::class);

        $university = $createUniversityService->execute($request->validated());

        return $this->created('University Created successfully', (new UniversityResource($university))->resolve());
    }

    /**
     * Display the specified resource.
     */
    public function show(University $university)
    {
        $this->authorize('view', $university);
        $university->load('admin:id,name');

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
        $this->authorize('update', $university);
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
        $this->authorize('delete', $university);
        DB::transaction(function () use ($university) {
            foreach ($university->faculties as $faculty) {
                $faculty->departments()->delete();
                $faculty->delete();
            }

            $university->delete();
        });

        return $this->ok(
            'University deleted successfully.'
        );
    }
}
