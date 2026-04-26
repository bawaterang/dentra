<?php

namespace App\Modules\Pendaftaran\routes;

use App\Modules\Pendaftaran\Http\Livewire\PendaftaranPage;
use App\Modules\Pendaftaran\Http\Livewire\FormPendaftaranPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('admisi/pendaftaran')->name('pendaftaran.')->group(function () {
    Route::get('/', PendaftaranPage::class)->name('index');
    Route::get('/create', FormPendaftaranPage::class)->name('create');
    Route::get('/print/{id}', [\App\Modules\Pendaftaran\Http\Controllers\PendaftaranPrintController::class, 'print'])->name('print')->middleware('signed');
});
