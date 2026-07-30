<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotifikasiAbsensiLog extends Model
{
    protected $table = 'notifikasi_absensi_log';

    protected $fillable = [
        'siswa_id',
        'siswa_nama',
        'tanggal',
        'jenis',
        'kanal',
        'kontak',
        'pesan',
        'status',
        'alasan_gagal',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];
}
