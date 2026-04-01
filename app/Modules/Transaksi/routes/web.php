<?php

namespace App\Modules\Transaksi\routes;

use App\Modules\Transaksi\Http\Livewire\TransaksiPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('transaksi')->name('transaksi.')->group(function () {
    Route::get('/', TransaksiPage::class)->name('index');
});
