<?php

namespace App\Modules\Reservasi\routes;

use App\Modules\Reservasi\Http\Livewire\ReservasiPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('admisi/reservasi')->name('reservasi.')->group(function () {
    Route::get('/', ReservasiPage::class)->name('index');
});
