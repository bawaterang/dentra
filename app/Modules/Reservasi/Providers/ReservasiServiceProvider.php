<?php

namespace App\Modules\Reservasi\Providers;

use Illuminate\Support\ServiceProvider;

class ReservasiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
