<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ami extends Model
{
    use HasFactory;

    protected $table = 'amis';

    protected $fillable = [
        'tahun_akademik_id',
        'temuan',
        'rekomendasi',
        'tindak_lanjut',
        'target_selesai',
        'tanggal_pelaksanaan',
        'input_by',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pelaksanaan' => 'date',
        ];
    }

    public function tahunAkademik(): BelongsTo
    {
        return $this->belongsTo(TahunAkademik::class);
    }

    public function penginput(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_by');
    }

    public function dokumenAmis(): HasMany
    {
        return $this->hasMany(DokumenAmi::class);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }
}
