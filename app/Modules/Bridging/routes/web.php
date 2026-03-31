<?php

namespace App\Modules\Bridging\routes;

use Illuminate\Support\Facades\Route;
use App\Modules\Bridging\Http\Livewire\SettingApiPage;
use App\Modules\Bridging\Http\Livewire\DataPasienBpjsPage;

Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('bridging')->name('bridging.')->group(function () {
        Route::get('/setting-api', SettingApiPage::class)->name('setting_api');
        Route::get('/data-pasien-bpjs', DataPasienBpjsPage::class)->name('data_pasien_bpjs');
    });
});
