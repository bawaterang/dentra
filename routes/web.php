<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard.index');
    }
    return view('pages.login');
})->name('login');

Route::get('/logout', function () {
    auth()->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('login');
})->name('logout');

use App\Http\Controllers\ChatController;

Route::middleware(['auth'])->prefix('chat')->group(function () {
    Route::get('/users', [ChatController::class, 'getUsers']);
    Route::get('/messages/{receiverId}', [ChatController::class, 'getMessages']);
    Route::post('/messages', [ChatController::class, 'sendMessage']);
    Route::post('/read/{senderId}', [ChatController::class, 'markAsRead']);
});
