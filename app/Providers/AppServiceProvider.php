<?php

namespace App\Providers;

use App\Models\Letter;
use App\Observers\LetterObserver;
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
    }
}
