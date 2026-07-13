<?php

use App\Http\Controllers\SiswaAuth\SiswaAbsensiController;
use App\Http\Controllers\SiswaAuth\SiswaDashboardController;
use App\Http\Controllers\SiswaAuth\SiswaFaceEnrollmentController;
use App\Http\Controllers\SiswaAuth\SiswaNewPasswordController;
use App\Http\Controllers\SiswaAuth\SiswaPasswordController;
use App\Http\Controllers\SiswaAuth\SiswaPasswordResetLinkController;
use App\Http\Controllers\SiswaAuth\SiswaProfileController;
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

        Route::get('forgot-password', [SiswaPasswordResetLinkController::class, 'create'])->name('password.request');
        Route::post('forgot-password', [SiswaPasswordResetLinkController::class, 'store'])->name('password.email');
        Route::get('reset-password/{token}', [SiswaNewPasswordController::class, 'create'])->name('password.reset');
        Route::post('reset-password', [SiswaNewPasswordController::class, 'store'])->name('password.store');
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

        Route::get('profil', [SiswaProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profil', [SiswaProfileController::class, 'update'])->name('profile.update');
        Route::put('profil/password', [SiswaPasswordController::class, 'update'])->name('password.update');
    });
});
