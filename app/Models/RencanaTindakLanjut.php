<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RencanaTindakLanjut extends Model
{
    use HasFactory;

    protected $fillable = [
        'temuan_id',
        'uraian_realisasi',
        'waktu_pelaksanaan',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'waktu_pelaksanaan' => 'date',
        ];
    }

    public function temuan(): BelongsTo
    {
        return $this->belongsTo(Temuan::class);
    }

    public function buktiTindakLanjuts(): HasMany
    {
        return $this->hasMany(BuktiTindakLanjut::class);
    }

    public function keputusanRtms(): HasMany
    {
        return $this->hasMany(KeputusanRtm::class);
    }

    public function hasEvidence(): bool
    {
        return $this->buktiTindakLanjuts()->exists();
    }

    public function canBeEditedBy(?User $user): bool
    {
        return $user && $user->hasAnyRole(['ketua-gkm', 'anggota-gkm']);
    }
}
