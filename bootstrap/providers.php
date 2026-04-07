<?php

use App\Modules\Antrian\Providers\AntrianServiceProvider;
use App\Modules\Bridging\Providers\BridgingServiceProvider;
use App\Modules\Dashboard\Providers\DashboardServiceProvider;
use App\Modules\Keuangan\Providers\KeuanganServiceProvider;
use App\Modules\Laporan\Providers\LaporanServiceProvider;
use App\Modules\Master\Providers\MasterServiceProvider;
use App\Modules\Pendaftaran\Providers\PendaftaranServiceProvider;
use App\Modules\Screening\Providers\ScreeningServiceProvider;
use App\Modules\Setting\Providers\SettingServiceProvider;
use App\Modules\Transaksi\Providers\TransaksiServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    DashboardServiceProvider::class,
    MasterServiceProvider::class,
    AntrianServiceProvider::class,
    PendaftaranServiceProvider::class,
    ScreeningServiceProvider::class,
    SettingServiceProvider::class,
    BridgingServiceProvider::class,
    TransaksiServiceProvider::class,
    KeuanganServiceProvider::class,
    LaporanServiceProvider::class,
];
