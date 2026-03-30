<?php

namespace App\Modules\Setting\routes;

use Illuminate\Support\Facades\Route;
use App\Modules\Setting\Http\Livewire\SettingKlinikPage;
use App\Modules\Setting\Http\Livewire\ProfilPage;
use App\Modules\Setting\Http\Livewire\JadwalDokterPage;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/setting-klinik', SettingKlinikPage::class)->name('setting.klinik');
    Route::get('/profil', ProfilPage::class)->name('profil.index');
    
    // Setting prefixed group
    Route::prefix('setting')->name('setting.')->group(function () {
        Route::get('/jadwal-dokter', JadwalDokterPage::class)->name('jadwal_dokter');
    });
});
