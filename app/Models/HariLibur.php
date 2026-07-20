<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class HariLibur extends Model
{
    protected $table = 'hari_libur';

    protected $fillable = [
        'tanggal',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    /**
     * True kalau tanggal ini libur: terdaftar manual di sini, ATAU jatuh
     * pada salah satu hari dalam seminggu yang dikonfigurasi sebagai libur
     * rutin lewat Pengaturan::hari_libur_mingguan (mis. Sabtu & Minggu),
     * supaya admin tidak perlu menambahkan akhir pekan satu per satu.
     */
    public static function isLibur(?Carbon $tanggal = null): bool
    {
        $tanggal ??= Carbon::today();

        if (in_array($tanggal->dayOfWeek, Pengaturan::get()->liburMingguan(), true)) {
            return true;
        }

        return static::whereDate('tanggal', $tanggal)->exists();
    }
}
