<?php

namespace App\Providers;

use App\Models\Letter;
use App\Models\University;
use App\Observers\LetterObserver;
use App\Observers\UniversityObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
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
    }
}
