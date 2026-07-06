<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RaiseLetterRequest;
use App\Http\Requests\StoreLetterRequest;
use App\Http\Requests\UpdateLetterRequest;
use App\Http\Resources\LetterBroadcastResource;
use App\Http\Resources\LetterResource;
use App\Models\Letter;
use App\Models\LetterBroadcast;
use App\Models\LetterFlow;
use App\Models\LetterSignature;
use App\Services\QrCodeService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
        $this->authorize('viewAny', Letter::class);
        $per_page = $request->query('per_page', 15);

        $userId = auth()->id();

        $letters = QueryBuilder::for(Letter::class)
            ->where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->allowedFilters(
                AllowedFilter::partial('title'),
                AllowedFilter::partial('letter_number'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('type'),
            )
            ->with([
                'sender:id,name',
                'receiver:id,name',
            ])
            ->latest()
            ->paginate($per_page);

        return $this->ok('Letters retrieved successfully', LetterResource::collection($letters)->response()->getData(true));
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
    public function store(StoreLetterRequest $request, QrCodeService $qrCodeService)
    {
        $this->authorize('create', Letter::class);
        $user = $request->user();
        $data = $request->validated();

        $data['original_sender_id'] = $user->id;
        $data['sender_id'] = $user->id;
        $data['status'] = 'pending';

        $data['letter_uuid'] = Str::uuid();
        $letter = DB::transaction(function () use ($data, $qrCodeService,$user) {

            $letter = Letter::create($data);

            $dataToBeHashed = [
                'letter_number' => $letter->letter_number,
                'type' => $letter->type,
                'title' => $letter->title,
                'body' => $letter->body,
                'original_sender_id' => $letter->original_sender_id,
                'academic_year_id' => $letter->academic_year_id,
                'payload' => $letter->payload ?? null,
            ];
            $hashData = hash_hmac(
                'sha256',
                json_encode($dataToBeHashed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                config('app.key')
            );

            $qrCodePath = $qrCodeService->generate($letter, 'public', 'qr-codes', 400);
            $letter->update([
                'verification_hash' => $hashData,
                'qr_code_path' => $qrCodePath,
            ]);
            LetterSignature::create([
                'letter_id' => $letter->id,
                'user_id' => $user->id,
            ]);

            return $letter;
        });

        if ($letter) {
            return $this->ok(
                'Letter created successfully',
                (new LetterResource(
                    $letter->fresh()->load([
                        'sender:id,name',
                        'receiver:id,name',
                    ])
                ))->resolve()
            );
        }

        return $this->error('Letter could not be created', 400);
    }

    /**
     * Display the specified resource.
     */
    public function show(Letter $letter)
    {
        $this->authorize('view', $letter);
        return $this->ok(
            'Letter retrieved successfully',
            (new LetterResource(
                $letter->load([
                    'sender:id,name',
                    'receiver:id,name',
                ])
            ))->resolve()
        );
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
        $this->authorize('update', $letter);
        $credentials = $request->validated();

        if ($letter->receiver_id === (int) $credentials['receiver_id']) {
            return $this->error("New receiver can't be the same as current one.", 400);
        }

        $letter->update($credentials);

        return $this->ok(
            'Letter updated successfully',
            (new LetterResource(
                $letter->load([
                    'sender:id,name',
                    'receiver:id,name',
                ])
            ))->resolve()
        );
    }

    public function recentLetters(Request $request)
    {
        $this->authorize('viewAny', Letter::class);
        $user = auth()->user();
        $letters = Letter::with([
            'sender:id,name',
            'receiver:id,name',
        ])
            ->where('receiver_id', $user->id)
            ->latest()
            ->take(3)
            ->get();

        return $this->ok('Letters retrieved successfully', (LetterResource::collection($letters->load(['sender:id,name', 'receiver:id,name'])))->resolve());
    }

    public function raiseLetter(Letter $letter, RaiseLetterRequest $request)
    {
        $this->authorize('raise', $letter);
        $user = auth()->user();
        $credentials = $request->validated();



        if ($letter->receiver_id === (int) $credentials['receiver_id']) {
            return $this->error("New receiver can't be the same as current one.", 400);
        }

        DB::transaction(function () use ($letter, $user, $credentials) {
            $oldReceiverId = $letter->receiver_id;

            LetterSignature::create([
                'letter_id' => $letter->id,
                'user_id' => $user->id,
            ]);

            $letter->update([
                'sender_id' => $user->id,
                'receiver_id' => $credentials['receiver_id'],
            ]);

            LetterFlow::create([
                'letter_id' => $letter->id,
                'action' => 'signed and raised',
                'actor_id' => $user->id,
                'from_recipient_id' => $oldReceiverId,
                'to_recipient_id' => $credentials['receiver_id'],
                'note' => $credentials['note'] ?? null,
            ]);
        });

        return $this->ok('Letter raised');
    }

    public function inbox(Request $request)
    {
        $this->authorize('viewAny', Letter::class);
        $user = $request->user();

        $letters = QueryBuilder::for(Letter::class)
            ->where('receiver_id', $user->id)
            ->with(['sender:id,name', 'receiver:id,name'])
            ->allowedFilters(
                AllowedFilter::exact('status'),
            )
            ->defaultSort('-created_at')
            ->get()
            ->map(function ($letter) use ($request) {
                return [
                    'inbox_type' => 'letter',
                    'created_at' => $letter->created_at,
                    'data' => (new LetterResource($letter))->resolve($request),
                ];
            });

        $broadcasts = LetterBroadcast::query()
            ->with('attachments')
            ->where('is_active', true)
            ->latest()
            ->get()
            ->map(function ($broadcast) use ($request) {
                return [
                    'inbox_type' => 'letter_broadcast',
                    'created_at' => $broadcast->created_at,
                    'data' => (new LetterBroadcastResource($broadcast))->resolve($request),
                ];
            });

        $inbox = $letters
            ->concat($broadcasts)
            ->sortByDesc('created_at')
            ->values();

        return $this->ok(
            'Inbox letters fetched successfully',
            $inbox->toArray()
        );
    }
    public function outbox(Request $request){
        $this->authorize('viewAny', Letter::class);
        $user = $request->user();
        if($user->isMinistryAdmin()){
            $broadcasts = LetterBroadcast::query()
                ->with('attachments')
                ->latest()
                ->get();

            return $this->ok("Broadcast letters fetched successfully", $broadcasts->toArray());
        }
        $letters = QueryBuilder::for(Letter::class)
            ->where('sender_id', $user->id)
            ->where('original_sender_id', $user->id)
            ->with(['sender:id,name', 'receiver:id,name'])
            ->allowedFilters(
                AllowedFilter::exact('status')
            )->defaultSort(
                '-created_at',
            )->get();

        return $this->ok('Outbox letters retrieved successfully', LetterResource::collection($letters)->response()->getData(true));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Letter $letter)
    {
        //
    }
}
