<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    protected $fillable = [
        'nama_sekolah',
        'jam_masuk',
        'batas_terlambat',
        'mulai_pulang',
        'batas_pulang',
        'jam_cek_belum_hadir',
        'simulasi_waktu',
        'lokasi_lat',
        'lokasi_lng',
        'lokasi_radius_meter',
        'ip_sekolah',
        'hari_libur_mingguan',
    ];

    protected $casts = [
        'simulasi_waktu' => 'datetime',
        'lokasi_lat' => 'decimal:7',
        'lokasi_lng' => 'decimal:7',
        'hari_libur_mingguan' => 'array',
    ];

    /**
     * Ambil baris pengaturan tunggal (buat default bila belum ada).
     */
    public static function get(): self
    {
        return static::firstOrCreate([], [
            'nama_sekolah' => 'SMKN 1 SINDANG',
            'jam_masuk' => '07:00',
            'batas_terlambat' => '08:00',
            'mulai_pulang' => '13:00',
        ]);
    }

    /**
     * Jam "sekarang" versi aplikasi: pakai simulasi_waktu kalau diisi
     * (fitur testing), kalau tidak pakai waktu asli. Semua pengecekan
     * "hari ini" di sisi siswa (dashboard, absen, libur) HARUS lewat sini
     * supaya konsisten dengan AbsensiRecorder — kalau tidak, saat simulasi
     * aktif kamera masih bisa dibuka padahal AbsensiRecorder akan menolaknya
     * sebagai hari libur, dan siswa baru tahu setelah wajahnya discan.
     */
    public function waktuSekarang(): Carbon
    {
        return $this->simulasi_waktu ?? Carbon::now();
    }

    public static function sekarang(): Carbon
    {
        return static::get()->waktuSekarang();
    }

    /**
     * True kalau titik sekolah + radius sudah dikonfigurasi lengkap
     * (all-or-nothing, supaya tidak ada state "radius ada tapi titik
     * sekolah kosong" atau sebaliknya).
     */
    public function lokasiAktif(): bool
    {
        return $this->lokasi_lat !== null
            && $this->lokasi_lng !== null
            && $this->lokasi_radius_meter !== null;
    }

    /**
     * True kalau daftar IP/CIDR jaringan sekolah sudah diisi admin. Beda
     * dari lokasiAktif(): ini murni sinyal audit (lihat ipCocok()), tidak
     * pernah dipakai buat menolak absen.
     */
    public function ipSekolahAktif(): bool
    {
        return filled($this->ip_sekolah);
    }

    /**
     * null = fitur belum dikonfigurasi (tidak ada dasar untuk menilai) atau
     * $ip tidak diketahui. true/false = hasil cocok/tidak terhadap daftar
     * ip_sekolah (dipisah koma, tiap entri boleh IP tunggal atau CIDR
     * IPv4, mis. "36.85.12.40, 114.79.0.0/16"). Sengaja tidak melempar
     * exception untuk entri yang salah format -- salah ketik admin cukup
     * bikin entri itu "tidak pernah cocok", bukan error 500 di form
     * pengaturan atau di absen siswa.
     */
    public function ipCocok(?string $ip): ?bool
    {
        if (! $this->ipSekolahAktif() || $ip === null) {
            return null;
        }

        $daftar = array_filter(array_map('trim', explode(',', $this->ip_sekolah)));

        foreach ($daftar as $entri) {
            if (str_contains($entri, '/')) {
                if ($this->ipDalamCidr($ip, $entri)) {
                    return true;
                }
            } elseif ($ip === $entri) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cek IPv4 $ip berada di dalam blok CIDR $cidr. IPv6 di luar scope --
     * mayoritas ISP rumah/sekolah di Indonesia masih kasih IPv4 publik ke
     * pelanggan biasa, dan salah format di sini cuma berakibat "tidak
     * cocok" (lihat ipCocok()), bukan gagal.
     */
    private function ipDalamCidr(string $ip, string $cidr): bool
    {
        [$subnet, $prefix] = array_pad(explode('/', $cidr, 2), 2, null);

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $prefix = (int) $prefix;

        if ($ipLong === false || $subnetLong === false || $prefix < 0 || $prefix > 32) {
            return false;
        }

        $mask = $prefix === 0 ? 0 : (-1 << (32 - $prefix));

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }

    /**
     * Daftar hari dalam seminggu yang otomatis libur (0=Minggu..6=Sabtu,
     * cocok dengan Carbon::dayOfWeek), mis. [0, 6] buat Sabtu & Minggu.
     * Kosong kalau belum dikonfigurasi — tidak ada hari yang otomatis libur.
     */
    public function liburMingguan(): array
    {
        return $this->hari_libur_mingguan ?? [];
    }

    /**
     * True kalau notifikasi peringatan dini "belum hadir" aktif — sama
     * seperti lokasiAktif(), presensi nilai jamnya sendiri yang jadi
     * penanda aktif/nonaktif, tidak ada toggle terpisah.
     */
    public function cekBelumHadirAktif(): bool
    {
        return $this->jam_cek_belum_hadir !== null;
    }
}
