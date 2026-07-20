<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absensi extends Model
{
    protected $table = 'absensi';

    protected $fillable = [
        'siswa_id',
        'kelas_id',
        'tanggal',
        'jam_masuk',
        'jam_pulang',
        'status',
        'metode',
        'liveness_verified',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    /**
     * Kelas siswa PADA SAAT baris ini ditulis (snapshot di kolom kelas_id),
     * bukan kelas siswa sekarang -- lihat migration add_kelas_id_to_absensi.
     * Dipakai buat filter/scoping supaya baris lama tetap "milik" kelas
     * lama meski siswanya sudah pindah kelas.
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    /**
     * Sengaja pakai kelas siswa SEKARANG (lewat Siswa::visibleTo), bukan
     * snapshot kelas_id kolom ini -- akses wali kelas dianggap "siapa yang
     * SAAT INI jadi murid binaan saya" (sama seperti Siswa::visibleTo &
     * Kelas::visibleTo di seluruh aplikasi), bukan "baris mana yang dulu
     * ditulis atas nama kelas saya". Ini yang bikin rekap harian & dashboard
     * tetap menampilkan riwayat lengkap murid yang baru pindah MASUK ke
     * kelas binaan, alih-alih kosong untuk tanggal sebelum kepindahannya.
     * Filter kelas_id EKSPLISIT di laporan (LaporanController::queryLaporan)
     * yang justru sengaja pakai snapshot ini -- itu soal "atribusi baris ke
     * kelas historisnya", beda dari "siapa yang boleh lihat baris ini".
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->isAdmin()
            ? $query
            : $query->whereHas('siswa', fn (Builder $q) => $q->visibleTo($user));
    }
}
