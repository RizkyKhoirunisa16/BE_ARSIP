<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OTPController;
use App\Http\Controllers\Api\SuratMasukController; // Persiapan untuk Controller Surat
use App\Http\Controllers\Api\SuratKeluarController;
use App\Http\Controllers\Api\KlasifikasiSuratController;
use App\Http\Controllers\Api\UserController;

/* --- Rute Publik (Bisa diakses tanpa login) --- */
Route::post('/login/step1', [OTPController::class, 'step1_verify_credentials']);
Route::post('/verify-otp', [OTPController::class, 'verifyOtp']);

/* --- Rute Terlindungi (Wajib pakai Bearer Token) --- */
Route::middleware('auth:sanctum')->group(function () {
    
    // 1. Cek User Login
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Kelola User
    Route::post('/manage-users', [UserController::class, 'store']);
    Route::get('/manage-users', [UserController::class, 'index']);
    Route::put('/manage-users/{id}', [UserController::class, 'update']);
    Route::delete('/manage-users/{id}', [UserController::class, 'destroy']);

    // --- FITUR DOWNLOAD & PREVIEW (Tambahkan di sini) ---
    Route::get('/surat-masuk/download/{id}', [SuratMasukController::class, 'download']);
    Route::get('/surat-masuk/preview/{id}', [SuratMasukController::class, 'preview']);
    
    Route::get('/surat-keluar/download/{id}', [SuratKeluarController::class, 'download']);
    Route::get('/surat-keluar/preview/{id}', [SuratKeluarController::class, 'preview']);

    // 2. Fitur Surat Masuk
    // apiResource otomatis membuat route index, store, update, delete
    Route::apiResource('surat-masuk', SuratMasukController::class);

    Route::apiResource('klasifikasi-surat', KlasifikasiSuratController::class);
    
    // 3. Fitur Surat Keluar (Nanti bisa ditambahkan di sini)
    Route::apiResource('surat-keluar', SuratKeluarController::class);

});
