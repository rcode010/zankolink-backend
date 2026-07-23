<?php

namespace App\Services;

use App\Mail\TwoFactorCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TwoFactorAuthenticationService
{
    /**
     * Create a new class instance.
     */
    public function execute(User $user)
    {
        $minuteKey = "otp:minute:{$user->id}";
        $hourKey   = "otp:hour:{$user->id}";
        $dayKey    = "otp:day:{$user->id}";

        if (RateLimiter::tooManyAttempts($minuteKey, 1)) {
            throw ValidationException::withMessages([
                'otp' => 'You can only request one OTP in a minute.',
            ]);
        }

        if (RateLimiter::tooManyAttempts($hourKey, 5)) {
            throw ValidationException::withMessages([
                'otp' => 'You have reached the hourly OTP limit.',
            ]);
        }

        if (RateLimiter::tooManyAttempts($dayKey, 10)) {
            throw ValidationException::withMessages([
                'otp' => 'You have reached the daily OTP limit.',
            ]);
        }

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

        RateLimiter::hit($minuteKey, 60);
        RateLimiter::hit($hourKey, 60 * 60);
        RateLimiter::hit($dayKey, 24 * 60 * 60);

        return $challengeToken;
    }
}
