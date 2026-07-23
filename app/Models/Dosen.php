<?php

namespace App\Models;

use App\Support\RoleSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dosen extends Model
{
    use HasFactory;

    protected $fillable = [
        'nip',
        'nidn',
        'nama_dosen',
        'file_penelitian',
    ];


    public function pengajars(): HasMany
    {
        return $this->hasMany(Pengajar::class);
    }

    public function perkuliahans(): BelongsToMany
    {
        return $this->belongsToMany(Perkuliahan::class, 'pengajars')
            ->withPivot('is_koordinator')
            ->withTimestamps();
    }

}
