<?php

namespace App\Modules\Setting\routes;

use Illuminate\Support\Facades\Route;
use App\Modules\Setting\Http\Livewire\SettingKlinikPage;
use App\Modules\Setting\Http\Livewire\ProfilPage;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/setting-klinik', SettingKlinikPage::class)->name('setting.klinik');
    Route::get('/profil', ProfilPage::class)->name('profil.index');
});
