<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiLokasiGagalLog extends Model
{
    protected $table = 'absensi_lokasi_gagal_log';

    protected $fillable = [
        'siswa_id',
        'lat',
        'lng',
        'accuracy',
        'jarak_meter',
        'alasan',
        'ip',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'accuracy' => 'decimal:2',
        'jarak_meter' => 'decimal:2',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
