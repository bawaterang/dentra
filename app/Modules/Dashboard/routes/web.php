<?php

use App\Modules\Dashboard\Http\Livewire\DashboardPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/dashboard', DashboardPage::class)->name('dashboard.index');
});
