<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLetterRequest;
use App\Models\Letter;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class LetterController extends Controller
{
    use ApiResponses;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);

        // Fetch paginated letters with eager-loaded sender and receiver fields.
        // We select only 'id' and 'name' for both relations to optimize query performance.
        $letters = Letter::with([
            'sender:id,name',
            'receiver:id,name',
        ])
            ->latest()
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
    public function update(Request $request, Letter $letter)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Letter $letter)
    {
        //
    }
}
