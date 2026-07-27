<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Pengaturan;
use App\Models\Siswa;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Tulis/timpa satu baris Absensi buat satu tanggal, dipakai di dua tempat
 * yang keduanya artinya "staf menetapkan status absensi siswa untuk
 * tanggal ini": AbsensiController::manual() (form input manual admin) dan
 * KoreksiAbsensiController::approve() (menyetujui pengajuan koreksi
 * siswa) -- diekstrak ke sini supaya logikanya (termasuk race-retry &
 * aturan kelas_id snapshot di bawah) tidak dobel-tulis dan gampang beda
 * perilaku diam-diam antara dua alur itu.
 */
class AbsensiManualWriter
{
    /**
     * $status harus salah satu dari hadir/terlambat/izin/sakit/alpha.
     * $percobaanKedua cuma kepakai kalau INSERT kita sendiri tabrakan
     * constraint unik siswa+tanggal karena race dengan penulisan lain
     * (mis. AbsensiRecorder yang tepat saat itu memproses scan wajah siswa
     * yang sama) -- re-fetch baris yang barusan menang race itu lalu
     * terapkan koreksi manual ini ke situ. Koreksi TETAP harus tersimpan,
     * tidak boleh gagal cuma karena kalah race.
     */
    public function tulis(Siswa $siswa, string $tanggal, string $status, ?string $keterangan = null, bool $percobaanKedua = false): void
    {
        // Status non-hadir (izin/sakit/alpha) tidak boleh menyisakan jam
        // masuk/pulang dari baris sebelumnya -- kalau tidak, koreksi manual
        // hari yang sudah lengkap (mis. hadir -> sakit setelah surat dokter
        // menyusul) bisa meninggalkan jam_pulang lama nempel di baris sakit.
        $statusHadir = in_array($status, ['hadir', 'terlambat'], true);

        // whereDate(), bukan firstOrNew(['tanggal' => ...]) langsung: kolom
        // tanggal tersimpan sebagai datetime penuh ("Y-m-d H:i:s"), sedangkan
        // $tanggal dari form cuma string "Y-m-d" mentah. Pencarian exact-
        // match dengan string mentah itu tidak akan pernah ketemu baris
        // yang sudah ada, jadi berakhir coba INSERT baris baru dan gagal
        // kena constraint unik siswa+tanggal.
        $absensi = Absensi::where('siswa_id', $siswa->id)
            ->whereDate('tanggal', $tanggal)
            ->first();

        // kelas_id cuma di-stamp untuk baris BARU -- baris yang sudah ada
        // menyimpan snapshot kelas siswa pada tanggal itu (lihat migration
        // add_kelas_id_to_absensi), jangan ditimpa jadi kelas siswa SEKARANG
        // cuma karena admin sedang mengoreksi status hari itu.
        if (! $absensi) {
            $absensi = new Absensi([
                'siswa_id' => $siswa->id,
                'kelas_id' => $siswa->kelas_id,
                'tanggal' => $tanggal,
            ]);
        }

        $absensi->status = $status;
        $absensi->metode = 'manual';
        $absensi->keterangan = $keterangan;
        // Pengaturan::sekarang() (bukan Carbon::now() langsung) supaya ikut
        // menghormati simulasi_waktu, konsisten dengan seluruh alur absensi
        // lain -- lihat catatan di Pengaturan::waktuSekarang().
        $absensi->jam_masuk = $statusHadir ? Pengaturan::sekarang()->format('H:i:s') : null;

        if (! $statusHadir) {
            $absensi->jam_pulang = null;
        }

        try {
            $absensi->save();
        } catch (UniqueConstraintViolationException $e) {
            if ($percobaanKedua) {
                throw $e;
            }

            $this->tulis($siswa, $tanggal, $status, $keterangan, percobaanKedua: true);
        }
    }
}
