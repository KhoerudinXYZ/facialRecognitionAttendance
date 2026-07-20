<?php

namespace App\Policies;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;

class SiswaPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    /**
     * Wali kelas hanya boleh menambahkan siswa ke kelas binaannya sendiri.
     * Sebelumnya ini hanya dijaga lewat validasi kelas_id di controller —
     * eksplisit di sini supaya tidak gampang bolong kalau alur input lain
     * (import, API, dst.) lupa menerapkan scoping yang sama. Juga dipakai
     * SiswaController::bulkMove() untuk cek kelas tujuan pindah massal.
     */
    public function create(User $user, Kelas $kelas): bool
    {
        return $kelas->wali_kelas_id === $user->id;
    }

    public function view(User $user, Siswa $siswa): bool
    {
        return $siswa->kelas->wali_kelas_id === $user->id;
    }

    public function update(User $user, Siswa $siswa): bool
    {
        return $siswa->kelas->wali_kelas_id === $user->id;
    }

    public function delete(User $user, Siswa $siswa): bool
    {
        return $siswa->kelas->wali_kelas_id === $user->id;
    }

    public function enroll(User $user, Siswa $siswa): bool
    {
        return $siswa->kelas->wali_kelas_id === $user->id;
    }
}
