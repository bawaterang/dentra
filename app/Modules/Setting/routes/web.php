<?php

namespace App\Modules\Setting\routes;

use Illuminate\Support\Facades\Route;
use App\Modules\Setting\Http\Livewire\SettingKlinikPage;
use App\Modules\Setting\Http\Livewire\ProfilPage;
use App\Modules\Setting\Http\Livewire\JadwalDokterPage;
use App\Modules\Setting\Http\Livewire\UserPage;
use App\Modules\Setting\Http\Livewire\RoleUserPage;
use App\Modules\Setting\Http\Livewire\BackupPage;
use App\Modules\Setting\Http\Livewire\AksesMenuPage;
use App\Modules\Setting\Http\Livewire\InformasiPage;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/setting-klinik', SettingKlinikPage::class)->name('setting.klinik');
    Route::get('/profil', ProfilPage::class)->name('profil.index');
    
    // Setting prefixed group
    Route::prefix('setting')->name('setting.')->group(function () {
        Route::get('/antrian', \App\Modules\Antrian\Http\Livewire\SettingAntrianPage::class)->name('antrian');
        
        Route::get('/informasi', InformasiPage::class)->name('informasi');
        Route::get('/informasi/print', [\App\Modules\Setting\Http\Controllers\InformasiExportController::class, 'print'])->name('informasi.print');
        Route::get('/informasi/export', [\App\Modules\Setting\Http\Controllers\InformasiExportController::class, 'exportExcel'])->name('informasi.export');
        
        Route::get('/jadwal-dokter', JadwalDokterPage::class)->name('jadwal_dokter');
        
        Route::get('/user', UserPage::class)->name('user');
        Route::get('/user/print', [\App\Modules\Setting\Http\Controllers\UserExportController::class, 'print'])->name('user.print');
        Route::get('/user/export', [\App\Modules\Setting\Http\Controllers\UserExportController::class, 'exportExcel'])->name('user.export');
        
        Route::get('/role-user', RoleUserPage::class)->name('role_user');
        Route::get('/role-user/print', [\App\Modules\Setting\Http\Controllers\RoleExportController::class, 'print'])->name('role_user.print');
        Route::get('/role-user/export', [\App\Modules\Setting\Http\Controllers\RoleExportController::class, 'exportExcel'])->name('role_user.export');
        
        Route::get('/backup-database', BackupPage::class)->name('backup_database');
        
        Route::get('/akses-menu', AksesMenuPage::class)->name('akses_menu');
    });
});
