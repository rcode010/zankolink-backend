<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLetterRequest;
use App\Http\Requests\UpdateLetterRequest;
use App\Models\Letter;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class LetterController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);

        $letters = QueryBuilder::for(Letter::class)
            ->with(['sender:id,name','receiver:id,name'])
            ->allowedFilters(
                AllowedFilter::exact('status')
            )->defaultSort(
                '-created_at',
            )
            ->paginate($perPage);


        return $this->ok('Letters retrieved successfully', $letters->toArray());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLetterRequest $request)
    {
        $data = $request->validated();
        $user = auth()->user();

        $data['sender_id'] = auth()->id();
        $data['status'] = 'pending';

        $letter = Letter::create($data);

        if ($letter) {
            return $this->ok('Letter created successfully', [$letter]);
        }

        return $this->error('Letter could not be created', 400);
    }

    /**
     * Display the specified resource.
     */
    public function show(Letter $letter)
    {

        return $this->ok('Letter retrieved successfully', $letter->load('sender:id,name', 'receiver:id,name')->toArray());
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Letter $letter)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLetterRequest $request, Letter $letter)
    {
        $credentials = $request->validated();

        if($letter->receiver_id === $credentials['receiver_id']){
            return $this->error("New receiver can't be the same as current one.", 400);
        }

        $letter->update($credentials);

        return $this->ok('Letter updated successfully', $letter->load('sender:id,name','receiver:id,name')->toArray());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Letter $letter)
    {
        //
    }
}
