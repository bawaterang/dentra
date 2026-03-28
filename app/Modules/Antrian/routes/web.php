<?php

namespace App\Modules\Antrian\routes;

use App\Modules\Antrian\Http\Livewire\AntrianPage;
use App\Modules\Antrian\Http\Livewire\AmbilAntrianPage;
use App\Modules\Antrian\Http\Livewire\MonitorAntrianPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('antrian')->name('antrian.')->group(function () {
    Route::get('/', AntrianPage::class)->name('index');
    Route::get('/ambil', AmbilAntrianPage::class)->name('ambil');
});

// Monitor antrian (public, no auth required for display screen)
Route::middleware(['web'])->group(function () {
    Route::get('/antrian/monitor', MonitorAntrianPage::class)->name('antrian.monitor');
});
