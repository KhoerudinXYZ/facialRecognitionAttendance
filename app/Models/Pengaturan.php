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
        'simulasi_waktu',
        'lokasi_lat',
        'lokasi_lng',
        'lokasi_radius_meter',
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
     * Daftar hari dalam seminggu yang otomatis libur (0=Minggu..6=Sabtu,
     * cocok dengan Carbon::dayOfWeek), mis. [0, 6] buat Sabtu & Minggu.
     * Kosong kalau belum dikonfigurasi — tidak ada hari yang otomatis libur.
     */
    public function liburMingguan(): array
    {
        return $this->hari_libur_mingguan ?? [];
    }
}
