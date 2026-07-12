<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsensiAuditLog extends Model
{
    protected $table = 'absensi_audit_log';

    protected $fillable = [
        'absensi_id',
        'siswa_id',
        'siswa_nama',
        'tanggal',
        'jam_masuk',
        'jam_pulang',
        'status',
        'metode',
        'keterangan',
        'dihapus_oleh_user_id',
        'dihapus_oleh_nama',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];
}
