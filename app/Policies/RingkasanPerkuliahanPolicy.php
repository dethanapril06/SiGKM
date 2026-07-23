<?php

namespace App\Policies;

use App\Models\RingkasanPerkuliahan;
use App\Models\User;

class RingkasanPerkuliahanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['ketua-gkm', 'anggota-gkm', 'koordinator-prodi']);
    }

    public function view(User $user, RingkasanPerkuliahan $ringkasan): bool
    {
        return $user->hasAnyRole(['ketua-gkm', 'anggota-gkm', 'koordinator-prodi']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('anggota_gkm');
    }

    public function update(User $user, RingkasanPerkuliahan $ringkasan): bool
    {
        return $user->hasRole('anggota_gkm')
            && in_array($ringkasan->status, ['draft', 'ditolak'], true);
    }

    public function delete(User $user, RingkasanPerkuliahan $ringkasan): bool
    {
        return $user->hasRole('anggota_gkm')
            && $ringkasan->status === 'draft';
    }

    public function submit(User $user, RingkasanPerkuliahan $ringkasan): bool
    {
        return $user->hasRole('anggota_gkm')
            && in_array($ringkasan->status, ['draft', 'ditolak'], true);
    }
}