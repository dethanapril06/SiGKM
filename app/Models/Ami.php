<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ami extends Model
{
    use HasFactory;

    protected $table = 'amis';

    protected $fillable = [
        'tahun_akademik_id',
        'tanggal_pelaksanaan',
        'file_ami',
        'file_tindak_lanjut',
        'file_dokumentasi',
        'file_absensi',
        'input_by',
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
}
