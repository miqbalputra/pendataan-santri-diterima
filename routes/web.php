<?php

use App\Http\Controllers\PendaftaranController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PendaftaranController::class, 'index']);
Route::post('/pendaftaran', [PendaftaranController::class, 'store']);

Route::prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/pendaftar/{id}', [AdminController::class, 'show'])->name('admin.show');
});
