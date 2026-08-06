@extends('layouts.app')
@section('content')
    <div class="d-flex justify-content-between py-3 mb-4">
        <h4 class="fw-bold">Detail Temuan {{ $temuan->kode_temuan }}</h4><a href="{{ route('temuan-evaluasi.fakultas') }}"
            class="btn btn-secondary">Kembali</a>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <x-detail-row label="Kode Temuan">
                <span class="badge bg-label-primary">
                    {{ $temuan->kode_temuan }}
                </span>
            </x-detail-row>
            <x-detail-row label="Semester">
                {{ $temuan->evaluasiIndikator?->semester?->label }}
            </x-detail-row>
            <x-detail-row label="Indikator">
                {{ $temuan->evaluasiIndikator?->sumber_kode }} —
                {{ $temuan->evaluasiIndikator?->sumber_uraian }}
            </x-detail-row>
            <x-detail-row label="Penanggung Jawab">
                {{ $temuan->nama_penanggung_jawab ?? '-' }}
            </x-detail-row>
            <x-detail-row label="Pernyataan Temuan">
                <span style="white-space:pre-line">
                    {{ $temuan->pernyataan }}
                </span>
            </x-detail-row>
            <x-detail-row label="Rencana Awal">
                <span style="white-space:pre-line">
                    {{ $temuan->rencana_awal ?: '-' }}
                </span>
            </x-detail-row>
            <x-detail-row label="Target Selesai">
                {{ $temuan->target_selesai ?: '-' }}
            </x-detail-row>
            <x-detail-row label="Target Capaian">
                <span style="white-space:pre-line">
                    {{ $temuan->target_capaian ?: '-' }}
                </span>
            </x-detail-row>
            <x-detail-row label="Status">
                <span class="badge bg-label-primary">
                    {{ ucfirst($temuan->status) }}
                </span>
            </x-detail-row>
            <x-detail-row label="Dibuat oleh">
                {{ $temuan->pembuat?->name ?? '-' }}
            </x-detail-row>
        </div>
    </div>
    <div class="card mb-4">
        <h5 class="card-header">Risiko</h5>
        <div class="card-body">
            @forelse($temuan->risikoTemuans as $risiko)
                <div class="border rounded p-3 mb-2">
                    <strong>{{ $risiko->tingkatRisiko?->nama_tingkat ?? 'Risiko' }}</strong>
                    <p class="mb-1">{{ $risiko->deskripsi_risiko }}</p><small>Dampak:
                        {{ $risiko->dampak_risiko ?: '-' }}</small>
            </div>@empty<span class="text-muted">Belum ada risiko.</span>
            @endforelse
        </div>
    </div>
    <div class="card">
        <h5 class="card-header">Realisasi Tindak Lanjut</h5>
        <div class="list-group list-group-flush">
            @forelse($temuan->rencanaTindakLanjuts as $rtl)
                <a href="{{ route('rtl.show', $rtl) }}"
                    class="list-group-item list-group-item-action"><strong>{{ $rtl->waktu_pelaksanaan?->format('d-m-Y') ?? '-' }}</strong> —
                {{ Str::limit($rtl->uraian_realisasi, 130) }}</a>@empty<div class="p-3 text-muted">Belum
                    ada Realisasi RTL.</div>
            @endforelse
        </div>
    </div>
@endsection
