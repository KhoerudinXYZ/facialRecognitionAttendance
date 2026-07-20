<?php

namespace App\Policies;

use App\Models\Absensi;
use App\Models\Siswa;
use App\Models\User;

class AbsensiPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * Wali kelas hanya boleh mencatat absensi manual untuk siswa di kelas
     * binaannya. Sebelumnya hanya dijaga lewat Siswa::visibleTo() di
     * controller — eksplisit di sini supaya konsisten dengan update/delete.
     */
    public function create(User $user, Siswa $siswa): bool
    {
        return $siswa->kelas->wali_kelas_id === $user->id;
    }

    public function update(User $user, Absensi $absensi): bool
    {
        return $absensi->siswa->kelas->wali_kelas_id === $user->id;
    }

    public function delete(User $user, Absensi $absensi): bool
    {
        return $absensi->siswa->kelas->wali_kelas_id === $user->id;
    }
}
