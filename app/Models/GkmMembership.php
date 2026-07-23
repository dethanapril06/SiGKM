<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GkmMembership extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_anggota',
        'nip',
        'peran',
        'tanggal_mulai',
        'tanggal_selesai',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'is_active' => 'boolean',
        ];
    }


    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeKetua(Builder $query): Builder
    {
        return $query->where('peran', 'ketua');
    }

    public function scopeAnggota(Builder $query): Builder
    {
        return $query->where('peran', 'anggota');
    }
}
