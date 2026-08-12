<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EvaluasiIndikator extends Model
{
    use HasFactory;

    protected $fillable = [
        'semester_id',
        'evaluatable_type',
        'evaluatable_id',
        'status_capaian',
        'nama_penanggung_jawab',
        'bukti_capaian',
        'catatan',
        'input_by',
        'verified_by',
        'verified_at',
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

    public function evaluatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function penginput(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function temuans(): HasMany
    {
        return $this->hasMany(Temuan::class);
    }

    public function scopeBelumTercapai(Builder $query): Builder
    {
        return $query->where('status_capaian', 'belum_tercapai');
    }

    public function scopeTercapai(Builder $query): Builder
    {
        return $query->where('status_capaian', 'tercapai');
    }

    public function getSumberKodeAttribute(): string
    {
        return match (true) {
            $this->evaluatable instanceof IndikatorMutu => $this->evaluatable->kode_indikator ?? '-',
            $this->evaluatable instanceof IndikatorKinerjaKegiatanSatuan => $this->evaluatable->kode_ikks ?? '-',
            default => '-',
        };
    }

    public function getSumberUraianAttribute(): string
    {
        return match (true) {
            $this->evaluatable instanceof IndikatorMutu => $this->evaluatable->isi_indikator,
            $this->evaluatable instanceof IndikatorKinerjaKegiatanSatuan => $this->evaluatable->uraian_ikks,
            default => '-',
        };
    }

    public function getSumberJenisAttribute(): string
    {
        return $this->evaluatable instanceof IndikatorKinerjaKegiatanSatuan
            ? 'Program Studi'
            : 'Fakultas';
    }

    public function getSumberKonteksAttribute(): string
    {
        if ($this->evaluatable instanceof IndikatorMutu) {
            return $this->evaluatable->standarMutu?->nama_standar ?? '-';
        }

        if ($this->evaluatable instanceof IndikatorKinerjaKegiatanSatuan) {
            $ikk = $this->evaluatable->indikatorKinerjaKegiatan;
            $iku = $ikk?->indikatorKinerjaUtama;
            $sasaran = $iku?->sasaranStrategis;

            return collect([
                $sasaran?->kode_sasaran,
                $iku?->kode_iku,
                $ikk?->kode_ikk,
            ])->filter()->join(' → ') ?: '-';
        }

        return '-';
    }

    public function getKodeStandarAttribute(): ?string
    {
        if ($this->evaluatable instanceof IndikatorMutu) {
            return $this->evaluatable->standarMutu?->kode_standar;
        }

        if ($this->evaluatable instanceof IndikatorKinerjaKegiatanSatuan) {
            return $this->evaluatable->indikatorKinerjaKegiatan?->kode_ikk;
        }

        return null;
    }

    public function getKodeIndikatorAttribute(): ?string
    {
        return $this->sumber_kode;
    }
}
