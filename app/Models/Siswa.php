<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Siswa extends Authenticatable
{
    protected $table = 'siswa';

    protected $fillable = [
        'nis',
        'nisn',
        'no_hp_orang_tua',
        'nama',
        'jenis_kelamin',
        'kelas_id',
        'foto',
        'is_active',
        'username',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function faceDescriptors(): HasMany
    {
        return $this->hasMany(FaceDescriptor::class, 'siswa_id');
    }

    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class, 'siswa_id');
    }

    public function isEnrolled(): bool
    {
        return $this->faceDescriptors()->exists();
    }

    public function isRegistered(): bool
    {
        return $this->username !== null;
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->isAdmin()
            ? $query
            : $query->whereIn('kelas_id', $user->kelasBinaan()->pluck('id'));
    }
}
