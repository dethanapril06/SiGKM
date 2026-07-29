@extends('layouts.app')
@section('content')
    <div class="d-flex justify-content-between py-3 mb-4">
        <h4 class="fw-bold">Detail Keputusan RTM</h4><a href="{{ route('keputusan-rtm.index') }}"
            class="btn btn-secondary">Kembali</a>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <x-detail-row label="RTM">
                <a href="{{ route('notulen-rtm.show', $keputusanRtm->notulenRtm) }}">
                    {{ $keputusanRtm->notulenRtm?->jadwalRtm?->judul }}
                </a>
            </x-detail-row>
            <x-detail-row label="Semester RTM">
                {{ $keputusanRtm->notulenRtm?->jadwalRtm?->semester?->label }}
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
    @php($temuan = $keputusanRtm->temuan)<div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5>Temuan yang Ditinjau</h5>
            @if ($temuan)
                <a href="{{ route('temuan.show', $temuan) }}" class="btn btn-sm btn-info">Detail Temuan</a>
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
                {{ $temuan?->evaluasiIndikator?->sumber_uraian }}
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
