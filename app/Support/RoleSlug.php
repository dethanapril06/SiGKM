<?php

namespace App\Support;

final class RoleSlug
{
    public const KETUA_GKM = 'ketua-gkm';
    public const ANGGOTA_GKM = 'anggota-gkm';
    public const KOORDINATOR_PRODI = 'koordinator-prodi';

    public static function all(): array
    {
        return [
            self::KETUA_GKM,
            self::ANGGOTA_GKM,
            self::KOORDINATOR_PRODI,
        ];
    }
}
