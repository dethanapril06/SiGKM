@extends('layouts.app')
@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Detail Notulen RTM</h4>
        <div>
            <a href="{{ route('notulen-rtm.download-pdf', $notulenRtm) }}" class="btn btn-primary me-2">
                <i class="bx bx-download"></i> Download File Gabungan (PDF)
            </a>
            <a href="{{ route('notulen-rtm.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <x-detail-row label="Jadwal RTM">
                <a href="{{ route('jadwal-rtm.show', $notulenRtm->jadwalRtm) }}">
                    {{ $notulenRtm->jadwalRtm?->judul }}
                </a>
            </x-detail-row>
            <x-detail-row label="Semester">
                {{ $notulenRtm->jadwalRtm?->semester?->label }}
            </x-detail-row>
            <x-detail-row label="Isi Notulen">
                <div class="notulen-content border rounded p-3 bg-light">
                    {!! $notulenRtm->isi_notulen !!}
                </div>
            </x-detail-row>
            <x-detail-row label="Status">
                <span class="badge bg-label-primary">
                    {{ ucfirst($notulenRtm->status) }}
                </span>
            </x-detail-row>
            <x-detail-row label="Penginput">
                {{ $notulenRtm->penginput?->name ?? '-' }}
            </x-detail-row>
            <x-detail-row label="Verifikator">
                {{ $notulenRtm->verifikator?->name ?? '-' }} @if ($notulenRtm->verified_at)
                    ({{ $notulenRtm->verified_at->format('d-m-Y H:i') }})
                @endif
            </x-detail-row>
            <x-detail-row label="Catatan Verifikasi">
                {{ $notulenRtm->catatan_verifikasi ?: '-' }}
            </x-detail-row>
        </div>
    </div>
    <div class="card mb-4">
        <h5 class="card-header">Lampiran Notulen RTM</h5>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="border rounded p-3 text-center h-100">
                        <i class="bx bxs-file-pdf fs-2 text-danger mb-2"></i>
                        <h6 class="fw-bold mb-1">1. Undangan Rapat (PDF)</h6>
                        <small class="text-muted d-block mb-2">Halaman Pertama PDF Gabungan</small>
                        @if($notulenRtm->file_undangan)
                            <a href="{{ asset('storage/'.$notulenRtm->file_undangan) }}" target="_blank" class="btn btn-sm btn-outline-danger mt-1">
                                <i class="bx bx-download"></i> Unduh / Lihat Undangan
                            </a>
                        @else
                            <span class="badge bg-label-secondary d-inline-block mt-1">Belum diunggah</span>
                        @endif
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="border rounded p-3 text-center h-100">
                        <i class="bx bxs-file-pdf fs-2 text-danger mb-2"></i>
                        <h6 class="fw-bold mb-1">3. Lembar Absensi (PDF)</h6>
                        <small class="text-muted d-block mb-2">Halaman Ketiga PDF Gabungan</small>
                        @if($notulenRtm->file_absensi)
                            <a href="{{ asset('storage/'.$notulenRtm->file_absensi) }}" target="_blank" class="btn btn-sm btn-outline-danger mt-1">
                                <i class="bx bx-download"></i> Unduh / Lihat Absensi
                            </a>
                        @else
                            <span class="badge bg-label-secondary d-inline-block mt-1">Belum diunggah</span>
                        @endif
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="border rounded p-3 text-center h-100">
                        <i class="bx bx-images fs-2 text-success mb-2"></i>
                        <h6 class="fw-bold mb-1">4. Dokumentasi Rapat (Foto)</h6>
                        <small class="text-muted d-block mb-2">Halaman Terakhir PDF Gabungan</small>
                        @if(count($notulenRtm->dokumentasi_list) > 0)
                            <div class="d-flex justify-content-center gap-2 flex-wrap mt-2">
                                @foreach($notulenRtm->dokumentasi_list as $index => $dokPath)
                                    <a href="{{ asset('storage/'.$dokPath) }}" target="_blank" class="d-inline-block border rounded p-1" title="Foto Dokumentasi {{ $index + 1 }}">
                                        <img src="{{ asset('storage/'.$dokPath) }}" style="height: 50px; width: 50px; object-fit: cover;" class="rounded">
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <span class="badge bg-label-secondary d-inline-block mt-1">Belum diunggah</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <style>
        .notulen-content ul, .notulen-content ol {
            padding-left: 1.5rem;
            margin-bottom: 0.75rem;
        }
    </style>
@endpush
