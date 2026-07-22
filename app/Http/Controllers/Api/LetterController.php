<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RaiseLetterRequest;
use App\Http\Requests\StoreLetterRequest;
use App\Http\Requests\UpdateLetterRequest;
use App\Http\Resources\LetterRecipientsResource;
use App\Http\Resources\LetterResource;
use App\Models\Letter;
use App\Models\LetterFlow;
use App\Models\LetterRecipient;
use App\QueryFilters\MultiRecipientUniversityFilter;
use App\Services\LetterService;
use App\Services\LetterVerificationHashService;
use App\Services\QrCodeService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Letters
 *
 * APIs for letter CRUDand raise.
 */
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
                'attachments',
            ])
            ->latest()
            ->paginate($per_page);

        return $this->ok('Letters retrieved successfully', LetterResource::collection($letters)->response()->getData(true));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLetterRequest $request, QrCodeService $qrCodeService, LetterVerificationHashService $letterVerificationHashService, LetterService $letterService)
    {
        $this->authorize('create', Letter::class);
        $user = $request->user();
        $data = $request->validated();

        $data['original_sender_id'] = $user->id;
        $data['sender_id'] = $user->id;
        $data['status'] = 'pending';

        $data['letter_uuid'] = (string) Str::uuid();

        $letter = $letterService->create($data, $qrCodeService, $user, $letterVerificationHashService, $request->file('files', []));

        if ($letter) {
            return $this->ok(
                'Letter created successfully',
                (new LetterResource(
                    $letter->fresh()->load([
                        'sender:id,name',
                        'receiver:id,name',
                        'attachments',
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
                    'attachments',
                ])
            ))->resolve()
        );
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
                    'attachments'
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
            'attachments',
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
            ->where('status','pending')
            ->with([
                'sender:id,name',
                'receiver:id,name',
                'attachments',
                'signatures',
            ])
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

        $multiRecipientLetters = collect();

        if (! $user->isMinistryAdmin()) {
            $multiRecipientLetters = LetterRecipient::query()
                ->where('recipient_id', $user->id)
                ->with([
                    'letter.sender:id,name',
                    'letter.attachments',
                ])
                ->latest()
                ->get()
                ->map(function ($recipient) use ($request) {
                    return [
                        'inbox_type' => 'multi_recipient_letter',
                        'created_at' => $recipient->letter->created_at,
                        'data' => (new LetterRecipientsResource($recipient->letter))->resolve($request),
                    ];
                });
        }

        $inbox = $letters
            ->concat($multiRecipientLetters)
            ->sortByDesc('created_at')
            ->values();



        return $this->ok(
            'Inbox letters fetched successfully',
            $inbox->toArray()
        );
    }

    public function outbox(Request $request)
    {
        $this->authorize('viewAny', Letter::class);
        $user = $request->user();

        $letters = QueryBuilder::for(Letter::class)
            ->where('original_sender_id', $user->id)
            ->where('receiver_id','!=', null)
            ->with([
                'sender:id,name',
                'receiver:id,name',
                'attachments',
                'signatures',
            ])
            ->allowedFilters(
                AllowedFilter::exact('status')
            )
            ->defaultSort('-created_at')
            ->get();

        return $this->ok('Outbox letters retrieved successfully', LetterResource::collection($letters)->response()->getData(true));
    }


    public function multiRecipientOutbox(Request $request)
    {
        $this->authorize('viewMultiRecipientLetter', Letter::class);

        $letters = QueryBuilder::for(Letter::class)
            ->where('receiver_id', null)
            ->with(['sender:id,name', 'attachments', 'recipients'])
            ->allowedFilters(
                AllowedFilter::partial('created_at'),
                AllowedFilter::custom(
                    'university',
                    new MultiRecipientUniversityFilter()
                )
            )
            ->defaultSort('-created_at')
            ->get();

        return $this->ok('Multi-Recipient letters fetched successfully', LetterRecipientsResource::collection($letters)->response()->getData(true));
    }

    public function archived(Request $request){
        $this->authorize('viewAny', Letter::class);
        $user = $request->user();

        $letters = QueryBuilder::for(Letter::class)
            ->where('status', '!=', 'pending')
            ->where(function ($query) use ($user) {
                $query->where('original_sender_id', $user->id)
                    ->orWhere('receiver_id', $user->id);
            })
            ->with([
                'sender:id,name',
                'receiver:id,name',
                'attachments',
                'signatures',
            ])
            ->get();
        // ToDo: Ministry admin

        return $this->ok(
            'Archived letters retrieved successfully',
            $letters->toArray()
        );
    }

}
