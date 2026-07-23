<?php

namespace App\Models;

use App\Support\WorkflowStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Temuan extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_temuan',
        'evaluasi_indikator_id',
        'nama_penanggung_jawab',
        'pernyataan',
        'rencana_awal',
        'target_selesai',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'target_selesai' => 'date',
        ];
    }

    public function evaluasiIndikator(): BelongsTo
    {
        return $this->belongsTo(EvaluasiIndikator::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function risikoTemuans(): HasMany
    {
        return $this->hasMany(RisikoTemuan::class);
    }

    public function rencanaTindakLanjuts(): HasMany
    {
        return $this->hasMany(RencanaTindakLanjut::class);
    }

    public function keputusanRtms(): HasMany
    {
        return $this->hasMany(KeputusanRtm::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '!=', WorkflowStatus::DITUTUP);
    }

    public function scopeDiajukan(Builder $query): Builder
    {
        return $query->where('status', WorkflowStatus::TERBUKA);
    }

    public function isClosed(): bool
    {
        return $this->status === WorkflowStatus::DITUTUP;
    }

    public function canBeEditedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasAnyRole(['ketua-gkm', 'anggota-gkm'])
            && in_array($this->status, [WorkflowStatus::DRAFT, WorkflowStatus::TERBUKA], true)
            && ($user->hasRole('ketua-gkm') || (int) $this->created_by === (int) $user->id);
    }

    public function isTerbuka(): bool
    {
        return $this->status === WorkflowStatus::TERBUKA;
    }
}
