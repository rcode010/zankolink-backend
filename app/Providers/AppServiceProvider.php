<?php

namespace App\Providers;

use App\Models\Letter;
use App\Observers\LetterObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

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
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return 'http://localhost:3000/reset-password?token=' . $token . '&email=' . $user->email;
        });
    }
}
