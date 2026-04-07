<?php

namespace App\Modules\Laporan\Providers;

use Illuminate\Support\ServiceProvider;

class LaporanServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
