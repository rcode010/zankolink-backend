<?php

namespace App\Services;

use App\Mail\TwoFactorCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TwoFactorAuthenticationService
{
    /**
     * Create a new class instance.
     */
    public function execute(User $user)
    {
        $otp = random_int(100000, 999999);
        $user->update([
            'two_factor_code' => Hash::make($otp),
            'two_factor_expires_at' => now()->addMinutes(10),
        ]);
        Auth::logout();
        //        $token = $user->createToken('api-token')->plainTextToken;
        $challengeToken = Str::random(64);

        cache()->put(
            "2fa_challenge_{$challengeToken}",
            $user->id,
            now()->addMinutes(10)
        );

        Mail::to($user->email)->queue(new TwoFactorCodeMail($otp, $user));

        return $challengeToken;
    }
}
