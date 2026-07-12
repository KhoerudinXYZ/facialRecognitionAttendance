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
})->purpose('Tandai alpha siswa yang belum absen hari ini & kirim notifikasi WhatsApp ke orang tua');

// Dijadwalkan larut malam (bukan tepat setelah mulai_pulang) supaya siswa
// yang secara sah check-in telat sekali di sore/malam hari (mis. lewat
// self-service setelah kegiatan ekstrakurikuler) tidak keburu ditandai
// alpha. AbsensiRecorder tetap menimpa baris alpha kalau siswa scan
// setelahnya, jadi ini aman walau jadwalnya meleset. Perlu cron beneran
// di server (`* * * * * php artisan schedule:run`) supaya ini benar-benar
// berjalan otomatis — tanpa itu, jalankan manual: `php artisan absensi:cek-alpha`.
Schedule::command('absensi:cek-alpha')->dailyAt('23:00');
