<?php

namespace App\Modules\Pendaftaran\Providers;

use Illuminate\Support\ServiceProvider;

class PendaftaranServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
