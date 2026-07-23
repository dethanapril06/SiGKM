<?php

namespace App\Policies;

use App\Models\TemuanEvaluasi;
use App\Models\User;

class TemuanEvaluasiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['ketua-gkm', 'anggota-gkm', 'koordinator-prodi']);
    }

    public function view(User $user, TemuanEvaluasi $temuan): bool
    {
        return $user->hasAnyRole(['ketua-gkm', 'anggota-gkm', 'koordinator-prodi']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('anggota_gkm');
    }

    public function update(User $user, TemuanEvaluasi $temuan): bool
    {
        return $user->hasRole('anggota_gkm')
            && in_array($temuan->status, ['draft', 'ditolak'], true);
    }

    public function delete(User $user, TemuanEvaluasi $temuan): bool
    {
        return $user->hasRole('anggota_gkm')
            && $temuan->status === 'draft';
    }

    public function submit(User $user, TemuanEvaluasi $temuan): bool
    {
        return $user->hasRole('anggota_gkm')
            && in_array($temuan->status, ['draft', 'ditolak'], true);
    }
}