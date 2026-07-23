<?php

namespace App\Models;

use App\Support\WorkflowStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RingkasanPerkuliahan extends Model
{
    use HasFactory;

    protected $fillable = [
        'jadwal_monev_id',
        'perkuliahan_id',
        'jumlah_pertemuan',
        'kesesuaian_materi',
        'metode_pembelajaran',
        'keterangan',
        'status',
        'input_by',
        'verified_by',
        'verified_at',
        'catatan_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_pertemuan' => 'integer',
            'input_by' => 'integer',
            'verified_by' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    public function jadwalMonev(): BelongsTo
    {
        return $this->belongsTo(JadwalMonev::class);
    }

    public function perkuliahan(): BelongsTo
    {
        return $this->belongsTo(Perkuliahan::class);
    }

    public function penginput(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', WorkflowStatus::DRAFT);
    }

    public function scopeDiajukan(Builder $query): Builder
    {
        return $query->where('status', WorkflowStatus::DIAJUKAN);
    }

    public function scopeTerlihatOlehKetua(Builder $query): Builder
    {
        return $query->whereIn('status', [
            WorkflowStatus::DIAJUKAN,
            WorkflowStatus::DIVERIFIKASI,
            WorkflowStatus::DITOLAK,
        ]);
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, [
            WorkflowStatus::DRAFT,
            WorkflowStatus::DITOLAK,
        ], true);
    }

    public function isDiajukan(): bool
    {
        return $this->status === WorkflowStatus::DIAJUKAN;
    }

    public function canBeEditedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasRole('ketua-gkm')) {
            return true;
        }

        return $this->canBeEdited() && (int) $user->id === (int) $this->input_by;
    }
}
