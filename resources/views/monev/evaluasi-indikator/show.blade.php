@extends('layouts.app')
@section('content')
    <div class="d-flex justify-content-between py-3 mb-4">
        <h4 class="fw-bold">Detail Evaluasi Indikator</h4>
        <a href="{{ route('evaluasi-indikator.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
    <div class="card mb-4"><div class="card-body">
        <x-detail-row label="Semester">{{ $evaluasiIndikator->semester?->label }}</x-detail-row>
        <x-detail-row label="Lingkup">{{ $evaluasiIndikator->sumber_jenis }} · {{ $evaluasiIndikator->sumber_konteks }}</x-detail-row>
        <x-detail-row label="Indikator">{{ $evaluasiIndikator->sumber_kode }} — {{ $evaluasiIndikator->sumber_uraian }}</x-detail-row>
        <x-detail-row label="Status Capaian"><span class="badge bg-label-primary">{{ str($evaluasiIndikator->status_capaian)->replace('_', ' ')->title() }}</span></x-detail-row>
        <x-detail-row label="Penanggung Jawab">{{ $evaluasiIndikator->nama_penanggung_jawab ?: '-' }}</x-detail-row>
        <x-detail-row label="Catatan"><span style="white-space:pre-line">{{ $evaluasiIndikator->catatan ?: '-' }}</span></x-detail-row>
        <x-detail-row label="Bukti">
            @if ($evaluasiIndikator->bukti_capaian)
                <a href="{{ asset('storage/'.$evaluasiIndikator->bukti_capaian) }}" target="_blank" class="btn btn-sm btn-info">Lihat Bukti</a>
            @else - @endif
        </x-detail-row>
        <x-detail-row label="Penginput">{{ $evaluasiIndikator->penginput?->name ?? '-' }}</x-detail-row>
    </div></div>
    <div class="card">
        <h5 class="card-header">Temuan Terkait</h5>
        <div class="list-group list-group-flush">
            @forelse($evaluasiIndikator->temuans as $item)
                <a href="{{ route('temuan-evaluasi.show', $item) }}" class="list-group-item list-group-item-action">
                    <strong>{{ $item->kode_temuan }}</strong> — {{ Str::limit($item->pernyataan, 120) }}
                </a>
            @empty
                <div class="p-3 text-muted">Tidak ada temuan.</div>
            @endforelse
        </div>
    </div>
@endsection
