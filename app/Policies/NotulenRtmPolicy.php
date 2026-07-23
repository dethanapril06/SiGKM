<?php

namespace App\Policies;

use App\Models\NotulenRtm;
use App\Models\User;

class NotulenRtmPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['ketua-gkm', 'anggota-gkm']);
    }

    public function view(User $user, NotulenRtm $notulenRtm): bool
    {
        return $user->hasAnyRole(['ketua-gkm', 'anggota-gkm']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['ketua-gkm', 'anggota-gkm']);
    }

    public function update(User $user, NotulenRtm $notulenRtm): bool
    {
        return $user->hasAnyRole(['ketua-gkm', 'anggota-gkm'])
            && in_array($notulenRtm->status, ['draft', 'ditolak'], true);
    }

    public function delete(User $user, NotulenRtm $notulenRtm): bool
    {
        return $user->hasAnyRole(['ketua-gkm', 'anggota-gkm'])
            && $notulenRtm->status === 'draft';
    }

    public function submit(User $user, NotulenRtm $notulenRtm): bool
    {
        return $user->hasAnyRole(['ketua-gkm', 'anggota-gkm'])
            && in_array($notulenRtm->status, ['draft', 'ditolak'], true);
    }

    public function verify(User $user, NotulenRtm $notulenRtm): bool
    {
        return $user->hasRole('ketua-gkm') && $notulenRtm->status === 'diajukan';
    }

    public function reject(User $user, NotulenRtm $notulenRtm): bool
    {
        return $this->verify($user, $notulenRtm);
    }
}
