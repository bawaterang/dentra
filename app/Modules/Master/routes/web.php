<?php

namespace App\Modules\Master\routes;

use App\Modules\Master\Http\Controllers\AsuransiExportController;
use App\Modules\Master\Http\Controllers\BmhpExportController;
use App\Modules\Master\Http\Controllers\DiagnosisExportController;
use App\Modules\Master\Http\Controllers\DokterExportController;
use App\Modules\Master\Http\Controllers\GigiExportController;
use App\Modules\Master\Http\Controllers\MenuExportController;
use App\Modules\Master\Http\Controllers\ObatExportController;
use App\Modules\Master\Http\Controllers\PasienExportController;
use App\Modules\Master\Http\Controllers\PoliExportController;
use App\Modules\Master\Http\Controllers\SurveiExportController;
use App\Modules\Master\Http\Controllers\TarifExportController;
use App\Modules\Master\Http\Controllers\TindakanExportController;
use App\Modules\Master\Http\Livewire\AsuransiPage;
use App\Modules\Master\Http\Livewire\BmhpPage;
use App\Modules\Master\Http\Livewire\DiagnosisPage;
use App\Modules\Master\Http\Livewire\DokterPage;
use App\Modules\Master\Http\Livewire\GigiPage;
use App\Modules\Master\Http\Livewire\MenuPage;
use App\Modules\Master\Http\Livewire\ObatPage;
use App\Modules\Master\Http\Livewire\PasienPage;
use App\Modules\Master\Http\Livewire\PoliPage;
use App\Modules\Master\Http\Livewire\SurveiPage;
use App\Modules\Master\Http\Livewire\TarifPage;
use App\Modules\Master\Http\Livewire\TindakanPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('master')->name('master.')->group(function () {
    // Pasien
    Route::get('/data-pasien', PasienPage::class)->name('pasien');
    Route::get('/data-pasien/print', [PasienExportController::class, 'print'])->name('pasien.print');
    Route::get('/data-pasien/export-excel', [PasienExportController::class, 'exportExcel'])->name('pasien.export');

    // Dokter
    Route::get('/data-dokter', DokterPage::class)->name('dokter');
    Route::get('/data-dokter/print', [DokterExportController::class, 'print'])->name('dokter.print');
    Route::get('/data-dokter/export-excel', [DokterExportController::class, 'exportExcel'])->name('dokter.export');

    // Asuransi
    Route::get('/data-asuransi', AsuransiPage::class)->name('asuransi');
    Route::get('/data-asuransi/print', [AsuransiExportController::class, 'print'])->name('asuransi.print');
    Route::get('/data-asuransi/export-excel', [AsuransiExportController::class, 'exportExcel'])->name('asuransi.export');

    // Obat
    Route::get('/data-obat', ObatPage::class)->name('obat');
    Route::get('/data-obat/print', [ObatExportController::class, 'print'])->name('obat.print');
    Route::get('/data-obat/export-excel', [ObatExportController::class, 'exportExcel'])->name('obat.export');

    // Diagnosis
    Route::get('/data-diagnosis', DiagnosisPage::class)->name('diagnosis');
    Route::get('/data-diagnosis/print', [DiagnosisExportController::class, 'print'])->name('diagnosis.print');
    Route::get('/data-diagnosis/export-excel', [DiagnosisExportController::class, 'exportExcel'])->name('diagnosis.export');

    // Kategori Gigi
    Route::get('/data-gigi', GigiPage::class)->name('gigi');
    Route::get('/data-gigi/print', [GigiExportController::class, 'print'])->name('gigi.print');
    Route::get('/data-gigi/export-excel', [GigiExportController::class, 'exportExcel'])->name('gigi.export');

    // Tindakan
    Route::get('/data-tindakan', TindakanPage::class)->name('tindakan');
    Route::get('/data-tindakan/print', [TindakanExportController::class, 'print'])->name('tindakan.print');
    Route::get('/data-tindakan/export-excel', [TindakanExportController::class, 'exportExcel'])->name('tindakan.export');

    // Tarif
    Route::get('/data-tarif', TarifPage::class)->name('tarif');
    Route::get('/data-tarif/print', [TarifExportController::class, 'print'])->name('tarif.print');
    Route::get('/data-tarif/export-excel', [TarifExportController::class, 'exportExcel'])->name('tarif.export');

    // BMHP
    Route::get('/data-bmhp', BmhpPage::class)->name('bmhp');
    Route::get('/data-bmhp/print', [BmhpExportController::class, 'print'])->name('bmhp.print');
    Route::get('/data-bmhp/export-excel', [BmhpExportController::class, 'exportExcel'])->name('bmhp.export');

    // Menu
    Route::get('/data-menu', MenuPage::class)->name('menu');
    Route::get('/data-menu/print', [MenuExportController::class, 'print'])->name('menu.print');
    Route::get('/data-menu/export-excel', [MenuExportController::class, 'exportExcel'])->name('menu.export');

    // Survei
    Route::get('/data-survei', SurveiPage::class)->name('survei');
    Route::get('/data-survei/print', [SurveiExportController::class, 'print'])->name('survei.print');
    Route::get('/data-survei/export-excel', [SurveiExportController::class, 'exportExcel'])->name('survei.export');

    // Poli
    Route::get('/data-poli', PoliPage::class)->name('poli');
    Route::get('/data-poli/print', [PoliExportController::class, 'print'])->name('poli.print');
    Route::get('/data-poli/export-excel', [PoliExportController::class, 'exportExcel'])->name('poli.export');
});
