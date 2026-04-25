<?php

namespace App\Providers;

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
    public function boot(): void
    {
        \App\Models\TrxAntrian::observe(\App\Observers\TrxAntrianObserver::class);
        \App\Models\TrxPendaftaran::observe(\App\Observers\TrxPendaftaranObserver::class);
        \App\Models\TrxScreening::observe(\App\Observers\TrxScreeningObserver::class);
    }
}
