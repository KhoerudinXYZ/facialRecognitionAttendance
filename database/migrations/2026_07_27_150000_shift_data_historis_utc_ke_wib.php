<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migrasi data SEKALI JALAN, bukan migrasi skema. App sebelumnya jalan di
 * config('app.timezone') = UTC (default Laravel yang tidak pernah diubah),
 * padahal sekolah di WIB (UTC+7) -- lihat PLAN-timezone-utc-ke-wib.md di
 * root repo untuk investigasi lengkapnya. Semua timestamp yang ditulis
 * lewat Carbon::now()/now() (langsung maupun lewat
 * Pengaturan::waktuSekarang()) selama ini sebenarnya berlabel jam yang
 * mundur 7 jam dari kejadian aslinya. Migrasi ini menggeser +7 jam supaya
 * data lama match dengan data baru yang mulai ditulis pakai
 * APP_TIMEZONE=Asia/Jakarta.
 *
 * TIDAK disentuh: Pengaturan::jam_masuk/batas_terlambat/mulai_pulang/
 * jam_cek_belum_hadir/simulasi_waktu -- itu nilai yang DIKETIK admin
 * (sudah dimaksudkan sebagai WIB dari awal), bukan hasil now(). Kolom
 * created_at/updated_at pengaturan sendiri tetap digeser seperti tabel
 * lain karena itu genuinely waktu insert/update baris.
 *
 * absensi baris id=170 (siswa_id 66, tanggal 2026-07-15) SENGAJA di-skip:
 * jam_masuk=jam_pulang=17:20:00 (pola data test lama, bukan absensi asli),
 * kalau digeser tanggal-nya akan tabrakan unique(siswa_id, tanggal) dengan
 * baris lain siswa yang sama di 2026-07-16 -- lihat diskusi commit ini.
 */
return new class extends Migration
{
    private const ROLLOVER_ABSENSI = [
        // id => [kolom => [lama, baru]], tanggal_baru -- urutan SENGAJA:
        // id=5 (siswa 2, geser ke 07-04) harus diproses SEBELUM id=3
        // (siswa 2, geser ke 07-03) supaya slot 07-03 kosong dulu sebelum
        // id=3 pindah ke situ -- kalau tidak, unique(siswa_id, tanggal)
        // bentrok di tengah proses (row 3 & 5 sama-sama siswa_id=2).
        5 => ['jam_masuk' => '00:53:05', 'tanggal' => '2026-07-04'],
        3 => ['jam_masuk' => '02:48:43', 'tanggal' => '2026-07-03'],
        7 => ['jam_masuk' => '01:10:20', 'tanggal' => '2026-07-04'],
        9 => ['jam_masuk' => '00:49:28', 'tanggal' => '2026-07-11'],
        169 => ['jam_pulang' => '00:45:13', 'tanggal' => '2026-07-15'],
    ];

    private const ROLLOVER_ABSENSI_ASAL = [
        3 => ['jam_masuk' => '19:48:43', 'tanggal' => '2026-07-02'],
        5 => ['jam_masuk' => '17:53:05', 'tanggal' => '2026-07-03'],
        7 => ['jam_masuk' => '18:10:20', 'tanggal' => '2026-07-03'],
        9 => ['jam_masuk' => '17:49:28', 'tanggal' => '2026-07-10'],
        169 => ['jam_pulang' => '17:45:13', 'tanggal' => '2026-07-14'],
    ];

    private const ROLLOVER_AUDIT_LOG = [
        2 => ['tanggal' => '2026-07-15'],
        3 => ['tanggal' => '2026-07-15'],
    ];

    private const ROLLOVER_AUDIT_LOG_ASAL = [
        2 => ['tanggal' => '2026-07-14'],
        3 => ['tanggal' => '2026-07-14'],
    ];

    private const TABEL_CREATED_UPDATED = [
        'absensi', 'absensi_audit_log', 'notifikasi_absensi_log',
        'pengajuan_izin', 'siswa', 'kelas', 'face_descriptors',
        'hari_libur', 'pengaturan', 'users',
    ];

    public function up(): void
    {
        // Migrasi data sekali-jalan buat data historis MySQL yang sudah
        // kadung ditulis pakai jam UTC -- tidak relevan (dan sintaks raw
        // SQL-nya MySQL-only, bukan portable) buat database SQLite
        // in-memory yang dipakai test suite, yang toh selalu mulai kosong
        // tanpa data lama buat digeser sama sekali.
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::transaction(function () {
            // created_at/updated_at di semua tabel yang relevan.
            foreach (self::TABEL_CREATED_UPDATED as $tabel) {
                DB::statement("UPDATE `{$tabel}` SET created_at = created_at + INTERVAL 7 HOUR, updated_at = updated_at + INTERVAL 7 HOUR");
            }
            DB::statement('UPDATE `siswa_password_reset_tokens` SET created_at = created_at + INTERVAL 7 HOUR');
            DB::statement('UPDATE `pengajuan_izin` SET reviewed_at = reviewed_at + INTERVAL 7 HOUR WHERE reviewed_at IS NOT NULL');

            // absensi.jam_masuk/jam_pulang -- baris normal (tidak rollover
            // tengah malam), aman digeser langsung.
            DB::statement("UPDATE `absensi` SET jam_masuk = ADDTIME(jam_masuk, '07:00:00') WHERE jam_masuk IS NOT NULL AND jam_masuk < '17:00:00'");
            DB::statement("UPDATE `absensi` SET jam_pulang = ADDTIME(jam_pulang, '07:00:00') WHERE jam_pulang IS NOT NULL AND jam_pulang < '17:00:00'");

            // absensi -- baris yang rollover lewat tengah malam, tanggal ikut +1.
            // id 170 SENGAJA tidak ada di daftar ini -- lihat catatan di atas.
            foreach (self::ROLLOVER_ABSENSI as $id => $baru) {
                DB::table('absensi')->where('id', $id)->update(array_filter([
                    'jam_masuk' => $baru['jam_masuk'] ?? null,
                    'jam_pulang' => $baru['jam_pulang'] ?? null,
                    'tanggal' => $baru['tanggal'],
                ], fn ($v) => $v !== null));
            }

            // absensi_audit_log -- sama seperti absensi, tapi tidak ada
            // unique(siswa_id, tanggal) di tabel ini jadi tidak ada risiko
            // tabrakan sama sekali.
            DB::statement("UPDATE `absensi_audit_log` SET jam_masuk = ADDTIME(jam_masuk, '07:00:00') WHERE jam_masuk IS NOT NULL AND jam_masuk < '17:00:00'");
            DB::statement("UPDATE `absensi_audit_log` SET jam_pulang = ADDTIME(jam_pulang, '07:00:00') WHERE jam_pulang IS NOT NULL AND jam_pulang < '17:00:00'");
            DB::table('absensi_audit_log')->where('id', 2)->update(['jam_masuk' => '00:29:57', 'jam_pulang' => '22:22:18', 'tanggal' => '2026-07-15']);
            DB::table('absensi_audit_log')->where('id', 3)->update(['jam_masuk' => '00:11:06', 'jam_pulang' => '00:11:22', 'tanggal' => '2026-07-15']);
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::transaction(function () {
            foreach (self::TABEL_CREATED_UPDATED as $tabel) {
                DB::statement("UPDATE `{$tabel}` SET created_at = created_at - INTERVAL 7 HOUR, updated_at = updated_at - INTERVAL 7 HOUR");
            }
            DB::statement('UPDATE `siswa_password_reset_tokens` SET created_at = created_at - INTERVAL 7 HOUR');
            DB::statement('UPDATE `pengajuan_izin` SET reviewed_at = reviewed_at - INTERVAL 7 HOUR WHERE reviewed_at IS NOT NULL');

            DB::statement("UPDATE `absensi` SET jam_masuk = SUBTIME(jam_masuk, '07:00:00') WHERE jam_masuk IS NOT NULL AND jam_masuk >= '07:00:00' AND id NOT IN (3,5,7,9,169)");
            DB::statement("UPDATE `absensi` SET jam_pulang = SUBTIME(jam_pulang, '07:00:00') WHERE jam_pulang IS NOT NULL AND jam_pulang >= '07:00:00' AND id NOT IN (3,5,7,9,169)");

            foreach (self::ROLLOVER_ABSENSI_ASAL as $id => $asal) {
                DB::table('absensi')->where('id', $id)->update(array_filter([
                    'jam_masuk' => $asal['jam_masuk'] ?? null,
                    'jam_pulang' => $asal['jam_pulang'] ?? null,
                    'tanggal' => $asal['tanggal'],
                ], fn ($v) => $v !== null));
            }

            DB::statement("UPDATE `absensi_audit_log` SET jam_masuk = SUBTIME(jam_masuk, '07:00:00') WHERE jam_masuk IS NOT NULL AND jam_masuk >= '07:00:00' AND id NOT IN (2,3)");
            DB::statement("UPDATE `absensi_audit_log` SET jam_pulang = SUBTIME(jam_pulang, '07:00:00') WHERE jam_pulang IS NOT NULL AND jam_pulang >= '07:00:00' AND id NOT IN (2,3)");
            DB::table('absensi_audit_log')->where('id', 2)->update(['jam_masuk' => '17:29:57', 'jam_pulang' => '15:22:18', 'tanggal' => '2026-07-14']);
            DB::table('absensi_audit_log')->where('id', 3)->update(['jam_masuk' => '17:11:06', 'jam_pulang' => '17:11:22', 'tanggal' => '2026-07-14']);
        });
    }
};
