<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Keuangan\Http\Livewire\BillingPage;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/keuangan/billing', BillingPage::class)->name('keuangan.billing');
});
