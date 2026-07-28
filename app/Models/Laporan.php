<?php

namespace App\Models;

use App\Support\WorkflowStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Laporan extends Model
{
    use HasFactory;

    protected $fillable = [
        'jenis_laporan',
        'semester_id',
        'jadwal_monev_id',
        'judul',
        'file_path',
        'status',
        'generated_by',
        'verified_by',
        'verified_at',
        'catatan_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function jadwalMonev(): BelongsTo
    {
        return $this->belongsTo(JadwalMonev::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeJenis(Builder $query, string $jenisLaporan): Builder
    {
        return $query->where('jenis_laporan', $jenisLaporan);
    }

    public function scopeDiajukan(Builder $query): Builder
    {
        return $query->where('status', WorkflowStatus::DIAJUKAN);
    }
}
