<?php

namespace App\Modules\Screening\routes;

use App\Modules\Screening\Http\Livewire\ScreeningPage;
use App\Modules\Screening\Http\Livewire\FormScreeningPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('admisi/screening-pasien')->name('screening.')->group(function () {
    Route::get('/', ScreeningPage::class)->name('index');
    Route::get('/form/{pendaftaranId}', FormScreeningPage::class)->name('form');
    Route::get('/print/{pendaftaranId}', [\App\Modules\Screening\Http\Controllers\ScreeningExportController::class, 'print'])->name('print');
    Route::get('/export-excel', [\App\Modules\Screening\Http\Controllers\ScreeningExportController::class, 'exportExcel'])->name('export');
});
