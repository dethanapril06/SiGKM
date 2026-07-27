<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeputusanRtm extends Model
{
    use HasFactory;

    protected $fillable = [
        'notulen_rtm_id',
        'temuan_id',
        'uraian_keputusan',
        'strategi',
        'target_selesai',
        'status',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function notulenRtm(): BelongsTo
    {
        return $this->belongsTo(NotulenRtm::class);
    }

    public function temuan(): BelongsTo
    {
        return $this->belongsTo(Temuan::class);
    }

    public function getRencanaTindakLanjutAttribute(): ?RencanaTindakLanjut
    {
        return $this->temuan?->rencanaTindakLanjuts?->first();
    }

    public function scopeBelumSelesai(Builder $query): Builder
    {
        return $query->where('status', '!=', 'selesai');
    }

    public function isOverdue(): bool
    {
        return false;
    }
}
