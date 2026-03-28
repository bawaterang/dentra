<?php

namespace App\Modules\Antrian\Providers;

use Illuminate\Support\ServiceProvider;

class AntrianServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
