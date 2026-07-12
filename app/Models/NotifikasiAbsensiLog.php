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
        'no_hp',
        'pesan',
        'status',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];
}
