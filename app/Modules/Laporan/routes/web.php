<?php

namespace App\Modules\Laporan\routes;

use App\Modules\Laporan\Http\Controllers\LaporanJasaMedisExportController;
use App\Modules\Laporan\Http\Controllers\LaporanKunjunganExportController;
use App\Modules\Laporan\Http\Livewire\LaporanJasaMedisPage;
use App\Modules\Laporan\Http\Livewire\LaporanKunjunganPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('laporan')->name('laporan.')->group(function () {
    // Jasa Medis
    Route::get('/jasa-medis', LaporanJasaMedisPage::class)->name('jasamedis');
    Route::get('/jasa-medis/print', [LaporanJasaMedisExportController::class, 'print'])->name('jasamedis.print');
    Route::get('/jasa-medis/export-excel', [LaporanJasaMedisExportController::class, 'exportExcel'])->name('jasamedis.export');

    // Kunjungan
    Route::get('/kunjungan', LaporanKunjunganPage::class)->name('kunjungan');
    Route::get('/kunjungan/print', [LaporanKunjunganExportController::class, 'print'])->name('kunjungan.print');
    Route::get('/kunjungan/export-excel', [LaporanKunjunganExportController::class, 'exportExcel'])->name('kunjungan.export');
    Route::get('/kunjungan/print-riwayat/{pasienId}', [LaporanKunjunganExportController::class, 'printRiwayat'])->name('kunjungan.print-riwayat');
});
