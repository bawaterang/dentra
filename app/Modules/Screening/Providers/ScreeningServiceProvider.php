<?php

namespace App\Modules\Screening\Providers;

use Illuminate\Support\ServiceProvider;

class ScreeningServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
