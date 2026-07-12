<?php

use App\Http\Controllers\SiswaAuth\SiswaAbsensiController;
use App\Http\Controllers\SiswaAuth\SiswaDashboardController;
use App\Http\Controllers\SiswaAuth\SiswaFaceEnrollmentController;
use App\Http\Controllers\SiswaAuth\SiswaRegistrationController;
use App\Http\Controllers\SiswaAuth\SiswaSessionController;
use Illuminate\Support\Facades\Route;

// Prefix 'portal' (bukan 'siswa') supaya tidak tertutup oleh wildcard
// route staff `siswa/{siswa}` (mis. GET /siswa/login akan salah tertangkap
// sebagai SiswaController@show dengan {siswa}='login' jika prefixnya sama).
Route::prefix('portal')->name('siswa.')->group(function () {
    Route::middleware('guest.siswa')->group(function () {
        Route::get('register', [SiswaRegistrationController::class, 'create'])->name('register');
        Route::post('register', [SiswaRegistrationController::class, 'store']);

        Route::get('login', [SiswaSessionController::class, 'create'])->name('login');
        Route::post('login', [SiswaSessionController::class, 'store']);
    });

    Route::middleware('auth.siswa')->group(function () {
        Route::post('logout', [SiswaSessionController::class, 'destroy'])->name('logout');

        Route::get('dashboard', [SiswaDashboardController::class, 'index'])->name('dashboard');

        Route::get('wajah', [SiswaFaceEnrollmentController::class, 'index'])->name('wajah');
        Route::get('enroll', [SiswaFaceEnrollmentController::class, 'create'])->name('enroll.create');
        Route::post('enroll', [SiswaFaceEnrollmentController::class, 'store'])->name('enroll.store');

        Route::get('absen', [SiswaAbsensiController::class, 'create'])->name('absen');
        Route::post('absen', [SiswaAbsensiController::class, 'store'])->name('absen.store');
        Route::get('riwayat', [SiswaAbsensiController::class, 'riwayat'])->name('riwayat');
    });
});
