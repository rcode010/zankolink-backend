<?php

namespace App\Providers;

use App\Models\Letter;
use App\Models\University;
use App\Observers\LetterObserver;
use App\Observers\UniversityObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
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
    }
}
