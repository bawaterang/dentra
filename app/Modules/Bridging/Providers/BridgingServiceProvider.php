<?php

namespace App\Modules\Bridging\Providers;

use Illuminate\Support\ServiceProvider;

class BridgingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'bridging');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
