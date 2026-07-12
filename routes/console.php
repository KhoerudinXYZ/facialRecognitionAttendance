<?php

use App\Services\AbsensiAlphaChecker;
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

// Dijalankan tiap jam (bukan sekali semalam) supaya orang tua siswa yang
// benar-benar tidak masuk tahu di hari yang sama, bukan tengah malam saat
// semuanya sudah lewat. AbsensiAlphaChecker sendiri yang menahan diri
// (tidak memproses apa pun) sampai beberapa jam setelah mulai_pulang,
// jadi aman dipanggil sesering ini — siswa yang scan telat tapi wajar
// tetap kebagian waktu sebelum ditandai alpha. Perlu cron beneran di
// server (`* * * * * php artisan schedule:run`) supaya ini benar-benar
// berjalan otomatis — tanpa itu, jalankan manual: `php artisan absensi:cek-alpha`.
Schedule::command('absensi:cek-alpha')->hourly()->between('12:00', '22:00');

// Backup harian (spatie/laravel-backup): dump database + storage/app/public
// (foto siswa yang diupload). clean lebih dulu supaya monitor menilai ukuran
// setelah backup lama dibuang, bukan sebelum. Sama seperti di atas, butuh
// cron beneran di server supaya berjalan otomatis.
Schedule::command('backup:clean')->dailyAt('01:00');
Schedule::command('backup:run')->dailyAt('01:30');
Schedule::command('backup:monitor')->dailyAt('02:00');
