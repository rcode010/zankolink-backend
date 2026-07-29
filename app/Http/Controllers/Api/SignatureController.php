<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSignatureRequest;
use App\Http\Resources\LetterSignatureResource;
use App\Models\Letter;
use App\Models\LetterSignature;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Signature
 *
 * APIs for signature showing and creation.
 */
class SignatureController extends Controller
{
    use ApiResponses;

    /**
     * Get a paginated list of signatures.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));
        $signatures = QueryBuilder::for(LetterSignature::class)
            ->with(['letter', 'user:id,name'])
            ->latest()
            ->paginate($perPage);

        return $this->ok(
            'Signatures retrieved successfully.',
            LetterSignatureResource::collection($signatures)
                ->response()
                ->getData(true)
        );
    }

    /**
     * Get a specific signature by ID.
     */
    public function show(int $id): JsonResponse
    {
        $signature = LetterSignature::with(['letter', 'user:id,name'])->find($id);

        if (! $signature) {
            return $this->error('Signature not found.', 404);
        }

        return $this->ok(
            'Signature retrieved successfully.',
            (new LetterSignatureResource($signature))->toArray(request())
        );
    }

    /**
     * Store a new signature and approve the letter.
     */
    public function store(StoreSignatureRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $letter = Letter::find($validated['letter_id']);
        $this->authorize('sign', $letter);
        $user = $request->user();

        // Check if the letter is already approved or rejected
        if (in_array($letter->status, ['approved', 'rejected'])) {
            return $this->error('This letter has already been processed.', 422);
        }

        DB::beginTransaction();

        try {
            $signature = LetterSignature::create([
                'letter_id' => $letter->id,
                'user_id' => $user->id,
                'comment' => $validated['comment'] ?? null,
            ]);

            DB::commit();

            return $this->ok(
                'Signature recorded and letter approved successfully.',
                (new LetterSignatureResource($signature->load(['letter', 'user'])))->toArray($request)
            );

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Signature Processing Failed: '.$e->getMessage());

            return $this->error('Failed to process signature.', 500);
        }
    }
}
