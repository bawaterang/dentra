<?php

namespace App\Modules\Keuangan\Providers;

use Illuminate\Support\ServiceProvider;

class KeuanganServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
