<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStampRequest;
use App\Models\Letter;
use App\Models\LetterStamp;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class LetterStampController extends Controller
{
    use ApiResponses;

    public function index(Request $request){

        $letterStamps = QueryBuilder::for(LetterStamp::class)->allowedFilters(
            AllowedFilter::exact('letter_id'),
            AllowedFilter::exact('user_id'),
        )->with(['letter:id,letter_number,title', 'user:id,name'])->paginate();

        return $this->ok("Letter stamps retrieved successfully",$letterStamps->toArray());

    }


    public function store(StoreStampRequest $request)
    {
        $user = $request->user();

        $letter = Letter::findOrFail($request->letter_id);

        $this->authorize('stamp', $letter);

        if ($letter->is_processed()) {
            return $this->error('Letter already processed', 422);
        }

        $letterStamp = LetterStamp::create([
            'user_id' => $user->id,
            'letter_id' => $letter->id,
            'comment' => $request->comment,
        ]);

        return $this->ok(
            'Successfully created letter stamp',
            $letterStamp->toArray()
        );
    }
}
