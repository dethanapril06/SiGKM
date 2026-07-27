<?php

namespace App\Helpers;

use App\Models\IndikatorKinerjaKegiatan;
use App\Models\IndikatorKinerjaKegiatanSatuan;
use App\Models\IndikatorKinerjaUtama;
use App\Models\IndikatorMutu;
use App\Models\SasaranStrategis;
use App\Models\StandarMutu;
use App\Models\Temuan;

final class CodeGenerator
{
    public static function kodeTemuan(?int $tahun = null): string
    {
        $tahun ??= now()->year;

        $nomorTerakhir = Temuan::query()
            ->where('kode_temuan', 'like', "TEM-{$tahun}-%")
            ->orderByDesc('kode_temuan')
            ->value('kode_temuan');

        $urutan = $nomorTerakhir
            ? ((int) substr($nomorTerakhir, -4)) + 1
            : 1;

        return sprintf('TEM-%d-%04d', $tahun, $urutan);
    }

    public static function kodeStandarMutu(): string
    {
        $allCodes = StandarMutu::query()->pluck('kode_standar');

        $maxNumber = 0;
        foreach ($allCodes as $code) {
            if ($code && preg_match('/(\d+)/', $code, $matches)) {
                $num = (int) $matches[1];
                if ($num > $maxNumber) {
                    $maxNumber = $num;
                }
            }
        }

        $nextNumber = $maxNumber + 1;

        return sprintf('STD-%03d', $nextNumber);
    }

    public static function kodeIndikatorMutu(int|StandarMutu $standar): string
    {
        $standarId = $standar instanceof StandarMutu
            ? $standar->id
            : $standar;

        if (! $standarId) {
            return 'IND-01';
        }

        $existingCodes = IndikatorMutu::query()
            ->where('standar_mutu_id', $standarId)
            ->pluck('kode_indikator');

        $maxNumber = 0;
        foreach ($existingCodes as $code) {
            if ($code && preg_match_all('/(\d+)/', $code, $matches)) {
                $lastNum = (int) end($matches[0]);
                if ($lastNum > $maxNumber) {
                    $maxNumber = $lastNum;
                }
            }
        }

        $nextNumber = $maxNumber + 1;

        return sprintf('IND-%02d', $nextNumber);
    }

    public static function kodeSasaran(): string
    {
        $allCodes = SasaranStrategis::query()->pluck('kode_sasaran');

        $maxNumber = 0;
        foreach ($allCodes as $code) {
            if ($code && preg_match_all('/(\d+)/', $code, $matches)) {
                $lastNum = (int) end($matches[0]);
                if ($lastNum > $maxNumber) {
                    $maxNumber = $lastNum;
                }
            }
        }

        $nextNumber = $maxNumber + 1;

        return sprintf('SS-%02d', $nextNumber);
    }

    public static function kodeIku(?int $sasaranId): string
    {
        if (! $sasaranId) {
            return 'IKU-01';
        }

        $existingCodes = IndikatorKinerjaUtama::query()
            ->where('sasaran_strategis_id', $sasaranId)
            ->pluck('kode_iku');

        $maxNumber = 0;
        foreach ($existingCodes as $code) {
            if ($code && preg_match_all('/(\d+)/', $code, $matches)) {
                $lastNum = (int) end($matches[0]);
                if ($lastNum > $maxNumber) {
                    $maxNumber = $lastNum;
                }
            }
        }

        $nextNumber = $maxNumber + 1;

        return sprintf('IKU-%02d', $nextNumber);
    }

    public static function kodeIkk(?int $ikuId): string
    {
        if (! $ikuId) {
            return 'IKK-01';
        }

        $existingCodes = IndikatorKinerjaKegiatan::query()
            ->where('indikator_kinerja_utama_id', $ikuId)
            ->pluck('kode_ikk');

        $maxNumber = 0;
        foreach ($existingCodes as $code) {
            if ($code && preg_match_all('/(\d+)/', $code, $matches)) {
                $lastNum = (int) end($matches[0]);
                if ($lastNum > $maxNumber) {
                    $maxNumber = $lastNum;
                }
            }
        }

        $nextNumber = $maxNumber + 1;

        return sprintf('IKK-%02d', $nextNumber);
    }

    public static function kodeIkks(?int $ikkId): string
    {
        if (! $ikkId) {
            return 'IKKS-01';
        }

        $existingCodes = IndikatorKinerjaKegiatanSatuan::query()
            ->where('indikator_kinerja_kegiatan_id', $ikkId)
            ->pluck('kode_ikks');

        $maxNumber = 0;
        foreach ($existingCodes as $code) {
            if ($code && preg_match_all('/(\d+)/', $code, $matches)) {
                $lastNum = (int) end($matches[0]);
                if ($lastNum > $maxNumber) {
                    $maxNumber = $lastNum;
                }
            }
        }

        $nextNumber = $maxNumber + 1;

        return sprintf('IKKS-%02d', $nextNumber);
    }
}
