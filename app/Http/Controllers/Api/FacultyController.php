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
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Faculty
 *
 * APIs for faculty CRUD.
 */
class FacultyController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Faculty::class);
        $per_page = max(1, min((int) $request->query('per_page', 15), 100));

        $query = Faculty::query();

        $user = auth()->user();

        $scope = $user->userScopes()
            ->where('scope_type', 'UNIVERSITY')
            ->first();

        // Only restrict if the user belongs to a university
        if ($scope) {
            $query->where(
                'university_id',
                $scope->scope_id
            );
        }

        $faculties = QueryBuilder::for($query)
            ->allowedFilters(
                AllowedFilter::partial('name'),
                AllowedFilter::exact('university_id'),
                'is_active',
            )
            ->with('university', 'admin:id,name')
            ->latest()
            ->paginate($per_page);

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
    public function store(StoreFacultyRequest $request)
    {
        $this->authorize('create', Faculty::class);

        $validated = $request->validated();

        $university = University::findOrFail($validated['university_id']);

        $this->authorize('createForUniversity', [Faculty::class, $university]);

        $faculty = Faculty::create($validated);

        $faculty->load('university:id,name', 'admin:id,name');

        return $this->success(
            'Faculty created successfully.',
            (new FacultyResource($faculty))->toArray($request),
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Faculty $faculty)
    {
        $this->authorize('view', $faculty);
        $faculty->load('university:id,name', 'admin:id,name');

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
        $this->authorize('update', $faculty);
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
        $this->authorize('delete', $faculty);
        DB::transaction(function () use ($faculty) {
            foreach ($faculty->departments as $department) {
                $department->delete();
            }
            $faculty->delete();
        });

        return $this->ok(
            'Faculty deleted successfully.',
        );
    }
}
