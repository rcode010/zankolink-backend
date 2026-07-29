<?php

namespace App\Providers;

use App\Models\Letter;
use App\Models\University;
use App\Observers\LetterObserver;
use App\Observers\UniversityObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Letter::observe(LetterObserver::class);
        University::observe(UniversityObserver::class);
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return 'https://e-zanko.vercel.app/reset-password?token='
                .$token
                .'&email='
                .urlencode($user->email);
        });

        Password::defaults(function () {
            return Password::min(8)
                ->symbols()
                ->mixedCase()
                ->numbers()
                ->uncompromised();
        });

        RateLimiter::for('login', function (Request $request)
        {
            $key = strtolower($request->email).'|'.$request->ip();

            return [
                Limit::perMinute(5)->by($key),
                Limit::perHour(20)->by($key),
                Limit::perDay(50)->by($key),
            ];
        });

        RateLimiter::for('verify', function (Request $request)
        {
            $key = $request->challenge_token.'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });

        RateLimiter::for('forgetPassword', function (Request $request)
        {
            $key = strtolower($request->email).'|'.$request->ip();

            return [
                Limit::perMinute(1)->by($key),
                Limit::perHour(5)->by($key),
                Limit::perDay(10)->by($key),
            ];
        });

        RateLimiter::for('resetPassword', function (Request $request)
        {
            $key = strtolower($request->email).'|'.$request->ip();

            return Limit::perMinute(10)->by($key);
        });

        RateLimiter::for('changePassword', function (Request $request)
        {
            return [
                Limit::perMinute(5)->by($request->user()->id),
                Limit::perHour(20)->by($request->user()->id),
            ];
        });

        RateLimiter::for('register', function (Request $request)
        {
            $key = $request->user()?->id ?: $request->ip();

            return Limit::perMinute(5)->by($key);
        });

        RateLimiter::for('api', function (Request $request)
        {
            $key = $request->user()?->id ?: $request->ip();

            return Limit::perMinute(100)->by($key);
        });
    }
}
