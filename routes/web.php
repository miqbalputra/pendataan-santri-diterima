<?php

use App\Http\Controllers\PendaftaranController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PendaftaranController::class, 'index']);
Route::get('/pendaftaran', [PendaftaranController::class, 'index']);
Route::post('/pendaftaran', [PendaftaranController::class, 'store']);
Route::get('/pendaftaran/sukses', function () {
    return view('sukses');
})->name('pendaftaran.sukses');
Route::get('/cek-status', [PendaftaranController::class, 'cekStatus'])->name('pendaftaran.cek_status');
Route::post('/cek-status', [PendaftaranController::class, 'cariStatus'])->name('pendaftaran.cari_status');
Route::get('/revisi/{token}', [PendaftaranController::class, 'editRevisi'])->name('pendaftaran.revisi');
Route::post('/revisi/{token}', [PendaftaranController::class, 'updateRevisi'])->name('pendaftaran.revisi.update');
Route::post('/upload-ocr', [PendaftaranController::class, 'uploadOcr'])->name('upload_ocr');
Route::get('/pendaftaran/{id}/cetak', [PendaftaranController::class, 'cetak'])->name('pendaftaran.cetak');
Route::get('/pendaftaran/{id}/bukti', [PendaftaranController::class, 'bukti'])->name('pendaftaran.bukti');
Route::get('/pendaftaran/{id}/berkas/{field}', [PendaftaranController::class, 'viewPublicDocument'])->name('pendaftaran.berkas');

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::prefix('admin')->middleware('auth')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::post('/settings', [AdminController::class, 'updateSettings'])->name('admin.settings.update');
    Route::post('/account', [AdminController::class, 'updateAccount'])->name('admin.account.update');
    Route::post('/test-ai', [AdminController::class, 'testAiConnection'])->name('admin.test_ai');
    Route::get('/pendaftar/{id}', [AdminController::class, 'show'])->name('admin.show');
    Route::get('/pendaftar/{id}/edit', [AdminController::class, 'edit'])->name('admin.edit');
    Route::post('/pendaftar/{id}', [AdminController::class, 'update'])->name('admin.update');
    Route::post('/pendaftar/{id}/status', [AdminController::class, 'updateStatus'])->name('admin.status');
    Route::post('/pendaftar/{id}/dokumen', [AdminController::class, 'updateDocumentVerification'])->name('admin.documents');
    Route::get('/pendaftar/{id}/berkas/{field}', [AdminController::class, 'viewUploadedDocument'])->name('admin.documents.view');
    
    // Fitur Baru
    Route::post('/periode', [AdminController::class, 'storePeriode'])->name('admin.periode.store');
    Route::post('/periode/{id}', [AdminController::class, 'updatePeriode'])->name('admin.periode.update');
    Route::get('/periode/{id}/delete', [AdminController::class, 'deletePeriode'])->name('admin.periode.delete');
    Route::post('/gelombang', [AdminController::class, 'storeGelombang'])->name('admin.gelombang.store');
    Route::post('/gelombang/{id}', [AdminController::class, 'updateGelombang'])->name('admin.gelombang.update');
    Route::get('/gelombang/{id}/delete', [AdminController::class, 'deleteGelombang'])->name('admin.gelombang.delete');
    Route::post('/pendaftar/{id}/follow-up', [AdminController::class, 'updateFollowUp'])->name('admin.followup.update');
    Route::get('/export', [AdminController::class, 'exportData'])->name('admin.export');
    Route::post('/ask-ai', [AdminController::class, 'askAi'])->name('admin.ask_ai');
});
