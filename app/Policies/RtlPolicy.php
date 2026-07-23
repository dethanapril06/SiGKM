<?php

namespace App\Policies;

use App\Models\Rtl;
use App\Models\User;

class RtlPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['ketua-gkm', 'anggota-gkm', 'koordinator-prodi']);
    }

    public function view(User $user, Rtl $rtl): bool
    {
        return $user->hasAnyRole(['ketua-gkm', 'anggota-gkm', 'koordinator-prodi']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('anggota_gkm');
    }

    public function update(User $user, Rtl $rtl): bool
    {
        return $user->hasRole('anggota_gkm')
            && in_array($rtl->status, ['draft', 'ditolak'], true);
    }

    public function delete(User $user, Rtl $rtl): bool
    {
        return $user->hasRole('anggota_gkm')
            && $rtl->status === 'draft';
    }

    public function submit(User $user, Rtl $rtl): bool
    {
        return $user->hasRole('anggota_gkm')
            && in_array($rtl->status, ['draft', 'ditolak'], true);
    }
}