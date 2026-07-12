<?php

use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FaceEnrollmentController;
use App\Http\Controllers\HariLiburController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\NotifikasiAbsensiController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SiswaController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::guard('web')->check()) {
        return redirect()->route('dashboard');
    }

    if (Auth::guard('siswa')->check()) {
        return redirect()->route('siswa.dashboard');
    }

    // Siswa jauh lebih banyak & lebih sering akses harian daripada staff,
    // jadi pengunjung tamu diarahkan ke portal siswa. Staff langsung ke /login.
    return redirect()->route('siswa.login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Kelas (lihat daftar: admin & wali kelas, dibatasi visibleTo(); ubah/hapus: admin saja)
    Route::get('kelas', [KelasController::class, 'index'])->name('kelas.index');
    Route::middleware('role:admin')->group(function () {
        Route::get('kelas/create', [KelasController::class, 'create'])->name('kelas.create');
        Route::post('kelas', [KelasController::class, 'store'])->name('kelas.store');
        Route::get('kelas/{kelas}/edit', [KelasController::class, 'edit'])->name('kelas.edit');
        Route::put('kelas/{kelas}', [KelasController::class, 'update'])->name('kelas.update');
        Route::delete('kelas/{kelas}', [KelasController::class, 'destroy'])->name('kelas.destroy');

        // Akun staff (admin saja)
        Route::resource('staff', \App\Http\Controllers\StaffUserController::class);
    });

    // Siswa
    Route::get('siswa/{siswa}/enroll', [SiswaController::class, 'enroll'])->name('siswa.enroll');
    Route::put('siswa/{siswa}/reset-akun', [SiswaController::class, 'resetAccount'])->name('siswa.resetAccount');
    Route::get('siswa/import', [SiswaController::class, 'importForm'])->name('siswa.import.form');
    Route::get('siswa/import/template', [SiswaController::class, 'importTemplate'])->name('siswa.import.template');
    Route::post('siswa/import', [SiswaController::class, 'import'])->name('siswa.import');
    Route::resource('siswa', SiswaController::class);

    // Pendaftaran wajah (API internal)
    Route::post('siswa/{siswa}/face', [FaceEnrollmentController::class, 'store'])->name('face.store');
    Route::delete('siswa/{siswa}/face', [FaceEnrollmentController::class, 'destroy'])->name('face.destroy');
    Route::delete('siswa/{siswa}/face/{faceDescriptor}', [FaceEnrollmentController::class, 'destroyOne'])
        ->scopeBindings()->name('face.destroyOne');

    // Absensi (kiosk kamera bersama dihapus — absen sekarang dilakukan mandiri oleh siswa di /portal/absen)
    Route::get('absensi', [AbsensiController::class, 'index'])->name('absensi.index');
    Route::post('absensi/manual', [AbsensiController::class, 'manual'])->name('absensi.manual');
    Route::delete('absensi/{absensi}', [AbsensiController::class, 'destroy'])->name('absensi.destroy');

    // Laporan
    Route::get('laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('laporan/excel', [LaporanController::class, 'exportExcel'])->name('laporan.excel');
    Route::get('laporan/pdf', [LaporanController::class, 'exportPdf'])->name('laporan.pdf');

    // Pengaturan & hari libur (admin saja)
    Route::middleware('role:admin')->group(function () {
        Route::get('pengaturan', [PengaturanController::class, 'edit'])->name('pengaturan.edit');
        Route::put('pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');
        Route::put('pengaturan/simulasi', [PengaturanController::class, 'updateSimulasi'])->name('pengaturan.simulasi');
        Route::put('pengaturan/lokasi', [PengaturanController::class, 'updateLokasi'])->name('pengaturan.lokasi');
        Route::put('pengaturan/libur-mingguan', [PengaturanController::class, 'updateLiburMingguan'])->name('pengaturan.libur-mingguan');

        Route::get('hari-libur', [HariLiburController::class, 'index'])->name('hari-libur.index');
        Route::post('hari-libur', [HariLiburController::class, 'store'])->name('hari-libur.store');
        Route::delete('hari-libur/{hariLibur}', [HariLiburController::class, 'destroy'])->name('hari-libur.destroy');

        Route::get('absensi/audit', [AbsensiController::class, 'audit'])->name('absensi.audit');

        Route::get('notifikasi-absensi', [NotifikasiAbsensiController::class, 'index'])->name('notifikasi-absensi.index');
    });

    // Profil (bawaan Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/siswa.php';
