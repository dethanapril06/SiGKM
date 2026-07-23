<?php

namespace App\Models;

use App\Support\RoleSlug;
use App\Traits\HasRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRole;

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function ringkasanPerkuliahansDiinput(): HasMany
    {
        return $this->hasMany(RingkasanPerkuliahan::class, 'input_by');
    }

    public function evaluasiIndikatorsDiinput(): HasMany
    {
        return $this->hasMany(EvaluasiIndikator::class, 'input_by');
    }

    public function temuansDibuat(): HasMany
    {
        return $this->hasMany(Temuan::class, 'created_by');
    }

    public function laporansDibuat(): HasMany
    {
        return $this->hasMany(Laporan::class, 'generated_by');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function dashboardRouteName(): string
    {
        return match ($this->role?->slug) {
            RoleSlug::KETUA_GKM => 'ketua-gkm.dashboard',
            RoleSlug::ANGGOTA_GKM => 'anggota-gkm.dashboard',
            RoleSlug::KOORDINATOR_PRODI => 'koordinator-prodi.dashboard',
            default => 'dashboard',
        };
    }
}
