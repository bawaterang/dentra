<?php

namespace App\Modules\Master\routes;

use App\Modules\Master\Http\Livewire\PasienPage;
use App\Modules\Master\Http\Livewire\DokterPage;
use App\Modules\Master\Http\Livewire\AsuransiPage;
use App\Modules\Master\Http\Livewire\ObatPage;
use App\Modules\Master\Http\Livewire\DiagnosisPage;
use App\Modules\Master\Http\Livewire\GigiPage;
use App\Modules\Master\Http\Livewire\TindakanPage;
use App\Modules\Master\Http\Livewire\TarifPage;
use App\Modules\Master\Http\Livewire\BmhpPage;
use App\Modules\Master\Http\Livewire\MenuPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('master')->name('master.')->group(function () {
    Route::get('/data-pasien', PasienPage::class)->name('pasien');
    Route::get('/data-pasien/print', [\App\Modules\Master\Http\Controllers\PasienExportController::class, 'print'])->name('pasien.print');
    Route::get('/data-pasien/export-excel', [\App\Modules\Master\Http\Controllers\PasienExportController::class, 'exportExcel'])->name('pasien.export');
    Route::get('/data-dokter', DokterPage::class)->name('dokter');
    Route::get('/data-dokter/print', [\App\Modules\Master\Http\Controllers\DokterExportController::class, 'print'])->name('dokter.print');
    Route::get('/data-dokter/export-excel', [\App\Modules\Master\Http\Controllers\DokterExportController::class, 'exportExcel'])->name('dokter.export');
    Route::get('/data-asuransi', AsuransiPage::class)->name('asuransi');
    Route::get('/data-obat', ObatPage::class)->name('obat');
    Route::get('/data-diagnosis', DiagnosisPage::class)->name('diagnosis');
    Route::get('/data-gigi', GigiPage::class)->name('gigi');
    Route::get('/data-tindakan', TindakanPage::class)->name('tindakan');
    Route::get('/data-tarif', TarifPage::class)->name('tarif');
    Route::get('/data-bmhp', BmhpPage::class)->name('bmhp');
    Route::get('/data-menu', MenuPage::class)->name('menu');
});
