<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanIzin extends Model
{
    protected $table = 'pengajuan_izin';

    protected $fillable = [
        'siswa_id',
        'tanggal',
        'jenis',
        'keterangan',
        'bukti',
        'status',
        'catatan_admin',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->isAdmin()
            ? $query
            : $query->whereHas('siswa', fn (Builder $q) => $q->visibleTo($user));
    }
}
