@extends('layouts.app')
@section('content')
    <div class="d-flex justify-content-between py-3 mb-4">
        <h4 class="fw-bold">Detail Realisasi Rencana Tindak Lanjut</h4>
        <a href="{{ route('rtl.fakultas') }}" class="btn btn-secondary">Kembali</a>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <x-detail-row label="Temuan">
                <a href="{{ route('temuan-evaluasi.show', $rtl->temuan) }}">
                    {{ $rtl->temuan?->kode_temuan }}
                </a> — {{ $rtl->temuan?->pernyataan }}
            </x-detail-row>
            <x-detail-row label="Semester">
                {{ $rtl->temuan?->evaluasiIndikator?->semester?->label }}
            </x-detail-row>
            <x-detail-row label="Indikator">
                {{ $rtl->temuan?->evaluasiIndikator?->sumber_kode }} —
                {{ $rtl->temuan?->evaluasiIndikator?->sumber_uraian }}
            </x-detail-row>
            <x-detail-row label="Penanggung Jawab Temuan">
                <span>
                    {{ $rtl->temuan?->nama_penanggung_jawab ?? '-' }}
                </span>
            </x-detail-row>
            <x-detail-row label="Rencana Awal RTL">
                <span style="white-space:pre-line">
                    {{ $rtl->temuan?->rencana_awal ?? '-' }}
                </span>
            </x-detail-row>
            <x-detail-row label="Target Selesai">
                {{ $rtl->temuan?->target_selesai ?: '-' }}
            </x-detail-row>
            <hr>
            <x-detail-row label="Uraian Realisasi RTL">
                <span style="white-space:pre-line">
                    {{ $rtl->uraian_realisasi }}
                </span>
            </x-detail-row>
            <x-detail-row label="Waktu Pelaksanaan">
                {{ $rtl->waktu_pelaksanaan?->format('d-m-Y') ?? '-' }}
            </x-detail-row>
            <x-detail-row label="Catatan Pelaksanaan">
                <span style="white-space:pre-line">
                    {{ $rtl->catatan ?: '-' }}
                </span>
            </x-detail-row>
            <x-detail-row label="Status Temuan">
                @if (($rtl->temuan?->status ?? '') === 'ditutup')
                    <span class="badge bg-label-success">Selesai / Ditutup</span>
                @else
                    <span class="badge bg-label-warning">Terbuka</span>
                @endif
            </x-detail-row>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <h5 class="card-header">Bukti Realisasi RTL</h5>
                <div class="card-body">
                    @forelse($rtl->buktiTindakLanjuts as $bukti)
                        <a href="{{ asset('storage/' . $bukti->file_path) }}" target="_blank"
                            class="btn btn-outline-primary mb-2">
                            <i class="bx bx-file"></i> {{ $bukti->keterangan ?: 'Bukti ' . $loop->iteration }}
                        </a>
                    @empty
                        <span class="text-muted">Belum ada bukti yang dilampirkan.</span>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <h5 class="card-header">Keputusan RTM Terkait</h5>
                <div class="list-group list-group-flush">
                    @forelse($rtl->keputusanRtms as $keputusan)
                        <a href="{{ route('keputusan-rtm.show', $keputusan) }}"
                            class="list-group-item list-group-item-action">{{ Str::limit($keputusan->uraian_keputusan, 120) }}</a>
                    @empty
                        <div class="p-3 text-muted">Belum dibahas pada RTM.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
