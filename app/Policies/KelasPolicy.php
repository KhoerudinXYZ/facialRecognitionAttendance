<?php

namespace App\Policies;

use App\Models\Kelas;
use App\Models\User;

class KelasPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function update(User $user, Kelas $kelas): bool
    {
        return false;
    }

    public function delete(User $user, Kelas $kelas): bool
    {
        return false;
    }
}
