<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    App\Modules\Dashboard\Providers\DashboardServiceProvider::class,
    App\Modules\Master\Providers\MasterServiceProvider::class,
];
