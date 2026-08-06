<?php

use App\Services\AbsensiAlphaChecker;
use App\Services\AbsensiBelumHadirChecker;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('absensi:cek-alpha', function (AbsensiAlphaChecker $checker) {
    $jumlah = $checker->jalankan();
    $this->info("{$jumlah} siswa ditandai alpha & diproses notifikasinya.");
})->purpose('Tandai alpha siswa yang belum absen hari ini & kirim notifikasi email ke orang tua');

Artisan::command('absensi:cek-belum-hadir', function (AbsensiBelumHadirChecker $checker) {
    $jumlah = $checker->jalankan();
    $this->info("{$jumlah} siswa belum hadir diberi notifikasi peringatan dini.");
})->purpose('Kirim peringatan dini ke orang tua siswa yang belum hadir di jam Pengaturan::jam_cek_belum_hadir');

// Dijalankan setiap hari jam 15:00 (3 PM) sesuai kebutuhan sekolah.
// Siswa aktif yang belum absen hari ini akan ditandai alpha dan dikirimi notifikasi.
Schedule::command('absensi:cek-alpha')->dailyAt('15:00')->withoutOverlapping();

// Backup harian (spatie/laravel-backup): dump database + storage/app/public
// (foto siswa yang diupload). clean lebih dulu supaya monitor menilai ukuran
// setelah backup lama dibuang, bukan sebelum. Sama seperti di atas, butuh
// cron beneran di server supaya berjalan otomatis.
Schedule::command('backup:clean')->dailyAt('01:00');
Schedule::command('backup:run')->dailyAt('01:30');
Schedule::command('backup:monitor')->dailyAt('02:00');
