<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiKecepatanAnomaliLog extends Model
{
    protected $table = 'absensi_kecepatan_anomali_log';

    protected $fillable = [
        'siswa_id',
        'lat_buka',
        'lng_buka',
        'lat_absen',
        'lng_absen',
        'jarak_meter',
        'jeda_ms',
        'kecepatan_kmh',
    ];

    protected $casts = [
        'lat_buka' => 'decimal:7',
        'lng_buka' => 'decimal:7',
        'lat_absen' => 'decimal:7',
        'lng_absen' => 'decimal:7',
        'jarak_meter' => 'decimal:2',
        'kecepatan_kmh' => 'decimal:2',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
