@extends('layouts.app')
@section('content')
    @php
        $isProdi = $keputusanRtm->temuan?->evaluasiIndikator?->evaluatable_type === 'ikks';
        $backRoute = $isProdi ? route('keputusan-rtm.prodi') : route('keputusan-rtm.fakultas');
    @endphp
    <div class="d-flex justify-content-between py-3 mb-4">
        <h4 class="fw-bold">Detail Keputusan RTM</h4>
        <a href="{{ $backRoute }}" class="btn btn-secondary">Kembali</a>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <x-detail-row label="Semester Keputusan">
                <strong>{{ $keputusanRtm->semester?->label ?? $keputusanRtm->notulenRtm?->jadwalRtm?->semester?->label ?? $keputusanRtm->temuan?->evaluasiIndikator?->semester?->label ?? '-' }}</strong>
            </x-detail-row>
            <x-detail-row label="Notulen RTM">
                @if ($keputusanRtm->notulenRtm)
                    <a href="{{ route('notulen-rtm.show', $keputusanRtm->notulenRtm) }}">
                        {{ $keputusanRtm->notulenRtm->jadwalRtm?->judul ?? 'Notulen RTM' }}
                    </a>
                    @if ($keputusanRtm->notulenRtm->jadwalRtm?->tanggal)
                        <span class="text-muted">({{ $keputusanRtm->notulenRtm->jadwalRtm->tanggal->format('d-m-Y') }})</span>
                    @endif
                @else
                    <span class="badge bg-label-secondary">Tanpa RTM (Opsional)</span>
                @endif
            </x-detail-row>
            <x-detail-row label="Uraian Keputusan">
                <span style="white-space:pre-line">
                    {{ $keputusanRtm->uraian_keputusan }}
                </span>
            </x-detail-row>
            <x-detail-row label="Strategi">
                <span style="white-space:pre-line">
                    {{ $keputusanRtm->strategi ?: '-' }}
                </span>
            </x-detail-row>
        </div>
    </div>
    @php($temuan = $keputusanRtm->temuan)
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5>Temuan yang Ditinjau</h5>
            @if ($temuan)
                <a href="{{ route('temuan-evaluasi.show', $temuan) }}" class="btn btn-sm btn-info">Detail Temuan</a>
            @endif
        </div>
        <div class="card-body">
            <x-detail-row label="Kode Temuan">
                {{ $temuan?->kode_temuan }}
            </x-detail-row>
            <x-detail-row label="Semester Temuan">
                {{ $temuan?->evaluasiIndikator?->semester?->label }}
            </x-detail-row>
            <x-detail-row label="Indikator">
                [{{ $temuan?->kode_standar ?? '-' }} &bull; {{ $temuan?->kode_indikator ?? '-' }}] {{ $temuan?->evaluasiIndikator?->sumber_uraian }}
            </x-detail-row>
            <x-detail-row label="Penanggung Jawab">
                {{ $temuan?->nama_penanggung_jawab ?: '-' }}
            </x-detail-row>
            <x-detail-row label="Pernyataan Temuan">
                <span style="white-space:pre-line">
                    {{ $temuan?->pernyataan }}
                </span>
            </x-detail-row>
        </div>
    </div>
@endsection
