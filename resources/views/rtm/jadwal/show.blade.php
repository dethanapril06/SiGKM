@extends('layouts.app')
@section('content')
    <div class="d-flex justify-content-between py-3 mb-4">
        <h4 class="fw-bold">Detail Jadwal RTM</h4><a href="{{ route('jadwal-rtm.index') }}"
            class="btn btn-secondary">Kembali</a>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <x-detail-row label="Judul">
                {{ $jadwalRtm->judul }}
            </x-detail-row>
            <x-detail-row label="Semester Pelaksanaan">
                {{ $jadwalRtm->semester?->label }}
            </x-detail-row>
            <x-detail-row label="Tanggal">
                {{ $jadwalRtm->tanggal?->format('d-m-Y') }}
            </x-detail-row>
            <x-detail-row label="Waktu">
                {{ $jadwalRtm->waktu_mulai ?: '-' }} s.d.
                {{ $jadwalRtm->waktu_selesai ?: '-' }}
            </x-detail-row>
            <x-detail-row label="Lokasi">
                {{ $jadwalRtm->lokasi ?: '-' }}
            </x-detail-row>
            <x-detail-row label="Agenda">
                <span style="white-space:pre-line">
                    {{ $jadwalRtm->agenda ?: '-' }}
                </span>
            </x-detail-row>
            <x-detail-row label="Status">
                <span class="badge bg-label-primary">
                    {{ ucfirst($jadwalRtm->status) }}
                </span>
            </x-detail-row>
            <x-detail-row label="Dibuat oleh">
                {{ $jadwalRtm->pembuat?->name ?? '-' }}
            </x-detail-row>
        </div>
    </div>
    @if ($jadwalRtm->notulenRtm)
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5>Notulen RTM</h5><a href="{{ route('notulen-rtm.show', $jadwalRtm->notulenRtm) }}"
                    class="btn btn-sm btn-info">Detail Notulen</a>
            </div>
            <div class="card-body">
                <div class="notulen-content border rounded p-3 bg-light mb-3">{!! $jadwalRtm->notulenRtm->isi_notulen !!}</div>
                <strong>{{ $jadwalRtm->notulenRtm->keputusanRtms->count() }} keputusan</strong>
            </div>
    </div>@else<div class="alert alert-info">Belum ada notulen.</div>
    @endif
@endsection
