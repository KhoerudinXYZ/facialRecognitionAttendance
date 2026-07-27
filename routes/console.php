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

// Dijalankan tiap jam (bukan sekali semalam) supaya orang tua siswa yang
// benar-benar tidak masuk tahu di hari yang sama, bukan tengah malam saat
// semuanya sudah lewat. AbsensiAlphaChecker sendiri yang menahan diri
// (tidak memproses apa pun) sampai Pengaturan::mulai_pulang — titik yang
// sama dengan penutupan absen masuk di AbsensiRecorder — jadi aman
// dipanggil sesering ini. Perlu cron beneran di server
// (`* * * * * php artisan schedule:run`) supaya ini benar-benar berjalan
// otomatis — tanpa itu, jalankan manual: `php artisan absensi:cek-alpha`.
Schedule::command('absensi:cek-alpha')->hourly()->between('12:00', '22:00');

// Beda dari alpha di atas: ini peringatan DINI (siswa mungkin masih dalam
// perjalanan), jadi telat sampai 1 jam (seperti alpha) mengurangi gunanya
// buat orang tua. Cron rapat tiap 10 menit tapi cuma di rentang jam pagi
// yang wajar (07:00-11:00) — di luar itu AbsensiBelumHadirChecker sendiri
// juga sudah menahan diri lewat jam_cek_belum_hadir & dedup notifikasi_
// absensi_log, tapi window ini menghindari polling sia-sia sepanjang hari.
// Diam-diam tidak melakukan apa pun kalau Pengaturan::jam_cek_belum_hadir
// belum diisi admin (lihat Pengaturan::cekBelumHadirAktif()).
Schedule::command('absensi:cek-belum-hadir')->everyTenMinutes()->between('07:00', '11:00');

// Backup harian (spatie/laravel-backup): dump database + storage/app/public
// (foto siswa yang diupload). clean lebih dulu supaya monitor menilai ukuran
// setelah backup lama dibuang, bukan sebelum. Sama seperti di atas, butuh
// cron beneran di server supaya berjalan otomatis.
Schedule::command('backup:clean')->dailyAt('01:00');
Schedule::command('backup:run')->dailyAt('01:30');
Schedule::command('backup:monitor')->dailyAt('02:00');
