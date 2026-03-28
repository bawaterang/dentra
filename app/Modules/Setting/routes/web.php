<?php

namespace App\Modules\Setting\routes;

use App\Modules\Setting\Http\Livewire\SettingAntrianPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('setting')->name('setting.')->group(function () {
    Route::get('/antrian', SettingAntrianPage::class)->name('antrian');
});
