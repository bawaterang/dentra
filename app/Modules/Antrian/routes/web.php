<?php

namespace App\Modules\Antrian\routes;

use App\Modules\Antrian\Http\Livewire\AntrianPage;
use App\Modules\Antrian\Http\Livewire\AmbilAntrianPage;
use App\Modules\Antrian\Http\Livewire\MonitorAntrianPage;
use App\Modules\Antrian\Http\Livewire\AmbilAntrianKioskPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('antrian')->name('antrian.')->group(function () {
    Route::get('/', AntrianPage::class)->name('index');
    Route::get('/ambil', AmbilAntrianPage::class)->name('ambil');
    Route::get('/cetak/{id}', \App\Modules\Antrian\Http\Livewire\CetakAntrianPage::class)->name('cetak');
});

// Monitor & Kiosk antrian (public, no auth required for display screen / self service)
Route::middleware(['web'])->group(function () {
    Route::get('/antrian/monitor', MonitorAntrianPage::class)->name('antrian.monitor');
    Route::get('/kiosk/antrian', AmbilAntrianKioskPage::class)->name('kiosk.antrian');
});
