<?php

namespace App\Modules\Master\Providers;

use Illuminate\Support\ServiceProvider;

class MasterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
