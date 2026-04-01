<?php

namespace App\Modules\Transaksi\Providers;

use Illuminate\Support\ServiceProvider;

class TransaksiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
