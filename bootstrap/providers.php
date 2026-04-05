<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    App\Modules\Dashboard\Providers\DashboardServiceProvider::class,
    App\Modules\Master\Providers\MasterServiceProvider::class,
    App\Modules\Antrian\Providers\AntrianServiceProvider::class,
    App\Modules\Pendaftaran\Providers\PendaftaranServiceProvider::class,
    App\Modules\Screening\Providers\ScreeningServiceProvider::class,
    App\Modules\Setting\Providers\SettingServiceProvider::class,
    App\Modules\Bridging\Providers\BridgingServiceProvider::class,
    App\Modules\Transaksi\Providers\TransaksiServiceProvider::class,
    App\Modules\Keuangan\Providers\KeuanganServiceProvider::class,
];
