<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Perkuliahan extends Model
{
    use HasFactory;

    protected $fillable = [
        'semester_id',
        'mata_kuliah_id',
        'kelas_id',
        'status',
    ];

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function mataKuliah(): BelongsTo
    {
        return $this->belongsTo(MataKuliah::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function pengajars(): HasMany
    {
        return $this->hasMany(Pengajar::class);
    }

    public function dosens(): BelongsToMany
    {
        return $this->belongsToMany(Dosen::class, 'pengajars')
            ->withPivot('is_koordinator')
            ->withTimestamps();
    }

    public function ringkasanPerkuliahans(): HasMany
    {
        return $this->hasMany(RingkasanPerkuliahan::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    public function getLabelAttribute(): string
    {
        $mataKuliah = $this->mataKuliah?->nama_mk ?? '-';
        $kelas = $this->kelas?->nama_kelas ?? '-';

        return "{$mataKuliah} - Kelas {$kelas}";
    }
}
