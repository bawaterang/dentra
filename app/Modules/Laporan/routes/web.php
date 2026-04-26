<?php

namespace App\Modules\Laporan\routes;

use App\Modules\Laporan\Http\Controllers\LaporanJasaMedisExportController;
use App\Modules\Laporan\Http\Controllers\LaporanKunjunganExportController;
use App\Modules\Laporan\Http\Livewire\LaporanJasaMedisPage;
use App\Modules\Laporan\Http\Livewire\LaporanKunjunganPage;
use App\Modules\Laporan\Http\Livewire\LaporanKritikSaranPage;
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
    Route::get('/kunjungan/print-riwayat/{pasienId}', [LaporanKunjunganExportController::class, 'printRiwayat'])->name('kunjungan.print-riwayat')->middleware('signed');

    // Kritik dan Saran
    Route::get('/kritik-saran', LaporanKritikSaranPage::class)->name('kritik-saran');
    Route::get('/kritik-saran/print', [\App\Modules\Laporan\Http\Controllers\LaporanKritikSaranExportController::class, 'print'])->name('kritik-saran.print');
    Route::get('/kritik-saran/export-excel', [\App\Modules\Laporan\Http\Controllers\LaporanKritikSaranExportController::class, 'exportExcel'])->name('kritik-saran.export');

    // Satu Sehat
    Route::get('/satu-sehat', \App\Modules\Laporan\Http\Livewire\LaporanSatuSehatPage::class)->name('satu-sehat');
    Route::get('/satu-sehat/print', [\App\Modules\Laporan\Http\Controllers\LaporanSatuSehatExportController::class, 'print'])->name('satu-sehat.print');
    Route::get('/satu-sehat/export-excel', [\App\Modules\Laporan\Http\Controllers\LaporanSatuSehatExportController::class, 'exportExcel'])->name('satu-sehat.export');

    // Pendapatan
    Route::get('/pendapatan', \App\Modules\Laporan\Http\Livewire\LaporanPendapatanPage::class)->name('pendapatan');
    Route::get('/pendapatan/print', [\App\Modules\Laporan\Http\Controllers\LaporanPendapatanExportController::class, 'print'])->name('pendapatan.print');
    Route::get('/pendapatan/export-excel', [\App\Modules\Laporan\Http\Controllers\LaporanPendapatanExportController::class, 'exportExcel'])->name('pendapatan.export');
});
