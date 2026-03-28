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
use App\Modules\Master\Http\Livewire\PoliPage;
use App\Modules\Master\Http\Livewire\SurveiPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('master')->name('master.')->group(function () {
    // Pasien
    Route::get('/data-pasien', PasienPage::class)->name('pasien');
    Route::get('/data-pasien/print', [\App\Modules\Master\Http\Controllers\PasienExportController::class, 'print'])->name('pasien.print');
    Route::get('/data-pasien/export-excel', [\App\Modules\Master\Http\Controllers\PasienExportController::class, 'exportExcel'])->name('pasien.export');

    // Dokter
    Route::get('/data-dokter', DokterPage::class)->name('dokter');
    Route::get('/data-dokter/print', [\App\Modules\Master\Http\Controllers\DokterExportController::class, 'print'])->name('dokter.print');
    Route::get('/data-dokter/export-excel', [\App\Modules\Master\Http\Controllers\DokterExportController::class, 'exportExcel'])->name('dokter.export');

    // Asuransi
    Route::get('/data-asuransi', AsuransiPage::class)->name('asuransi');
    Route::get('/data-asuransi/print', [\App\Modules\Master\Http\Controllers\AsuransiExportController::class, 'print'])->name('asuransi.print');
    Route::get('/data-asuransi/export-excel', [\App\Modules\Master\Http\Controllers\AsuransiExportController::class, 'exportExcel'])->name('asuransi.export');

    // Obat
    Route::get('/data-obat', ObatPage::class)->name('obat');
    Route::get('/data-obat/print', [\App\Modules\Master\Http\Controllers\ObatExportController::class, 'print'])->name('obat.print');
    Route::get('/data-obat/export-excel', [\App\Modules\Master\Http\Controllers\ObatExportController::class, 'exportExcel'])->name('obat.export');

    // Diagnosis
    Route::get('/data-diagnosis', DiagnosisPage::class)->name('diagnosis');
    Route::get('/data-diagnosis/print', [\App\Modules\Master\Http\Controllers\DiagnosisExportController::class, 'print'])->name('diagnosis.print');
    Route::get('/data-diagnosis/export-excel', [\App\Modules\Master\Http\Controllers\DiagnosisExportController::class, 'exportExcel'])->name('diagnosis.export');

    // Kategori Gigi
    Route::get('/data-gigi', GigiPage::class)->name('gigi');
    Route::get('/data-gigi/print', [\App\Modules\Master\Http\Controllers\GigiExportController::class, 'print'])->name('gigi.print');
    Route::get('/data-gigi/export-excel', [\App\Modules\Master\Http\Controllers\GigiExportController::class, 'exportExcel'])->name('gigi.export');

    // Tindakan
    Route::get('/data-tindakan', TindakanPage::class)->name('tindakan');
    Route::get('/data-tindakan/print', [\App\Modules\Master\Http\Controllers\TindakanExportController::class, 'print'])->name('tindakan.print');
    Route::get('/data-tindakan/export-excel', [\App\Modules\Master\Http\Controllers\TindakanExportController::class, 'exportExcel'])->name('tindakan.export');

    // Tarif
    Route::get('/data-tarif', TarifPage::class)->name('tarif');
    Route::get('/data-tarif/print', [\App\Modules\Master\Http\Controllers\TarifExportController::class, 'print'])->name('tarif.print');
    Route::get('/data-tarif/export-excel', [\App\Modules\Master\Http\Controllers\TarifExportController::class, 'exportExcel'])->name('tarif.export');

    // BMHP
    Route::get('/data-bmhp', BmhpPage::class)->name('bmhp');
    Route::get('/data-bmhp/print', [\App\Modules\Master\Http\Controllers\BmhpExportController::class, 'print'])->name('bmhp.print');
    Route::get('/data-bmhp/export-excel', [\App\Modules\Master\Http\Controllers\BmhpExportController::class, 'exportExcel'])->name('bmhp.export');

    // Menu
    Route::get('/data-menu', MenuPage::class)->name('menu');
    Route::get('/data-menu/print', [\App\Modules\Master\Http\Controllers\MenuExportController::class, 'print'])->name('menu.print');
    Route::get('/data-menu/export-excel', [\App\Modules\Master\Http\Controllers\MenuExportController::class, 'exportExcel'])->name('menu.export');

    // Survei
    Route::get('/data-survei', SurveiPage::class)->name('survei');
    Route::get('/data-survei/print', [\App\Modules\Master\Http\Controllers\SurveiExportController::class, 'print'])->name('survei.print');
    Route::get('/data-survei/export-excel', [\App\Modules\Master\Http\Controllers\SurveiExportController::class, 'exportExcel'])->name('survei.export');

    // Poli
    Route::get('/data-poli', PoliPage::class)->name('poli');
});
