<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MataKuliah extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_mk',
        'nama_mk',
        'sks',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sks' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function perkuliahans(): HasMany
    {
        return $this->hasMany(Perkuliahan::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getLabelAttribute(): string
    {
        return "{$this->kode_mk} - {$this->nama_mk}";
    }

    public function getNamaLengkapAttribute(): string
    {
        $sksFormatted = number_format((float) $this->sks, 2);

        return "{$this->kode_mk} - {$this->nama_mk} ({$sksFormatted} SKS)";
    }
}
