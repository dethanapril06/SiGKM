@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between py-3 mb-4">
    <h4 class="fw-bold">Detail AMI</h4>
    <a href="{{ route('ami.index') }}" class="btn btn-secondary"><i class="bx bx-arrow-back"></i> Kembali</a>
</div>

<div class="card mb-4">
    <h5 class="card-header">Informasi AMI</h5>
    <div class="card-body">
        <x-detail-row label="Tahun Akademik">{{ $ami->tahunAkademik?->nama }}</x-detail-row>
        <x-detail-row label="Tanggal Pelaksanaan">{{ $ami->tanggal_pelaksanaan?->format('d-m-Y') }}</x-detail-row>
        <x-detail-row label="Penginput">{{ $ami->penginput?->name ?? '-' }}</x-detail-row>
    </div>
</div>

<div class="card">
    <h5 class="card-header">Berkas AMI</h5>
    <div class="card-body">
        @php
            $fileFields = [
                'file_ami'           => 'File AMI',
                'file_tindak_lanjut' => 'File Tindak Lanjut',
                'file_dokumentasi'   => 'File Dokumentasi',
                'file_absensi'       => 'File Absensi',
            ];
        @endphp
        <div class="row">
            @foreach($fileFields as $field => $label)
                <div class="col-md-6 mb-3">
                    <div class="border rounded p-3">
                        <div class="fw-semibold mb-2"><i class="bx bx-file me-1"></i>{{ $label }}</div>
                        @if($ami->$field)
                            <a href="{{ asset('storage/' . $ami->$field) }}" target="_blank"
                                class="btn btn-sm btn-outline-primary">
                                <i class="bx bx-download"></i> Lihat / Unduh
                            </a>
                        @else
                            <span class="text-muted">Belum ada file</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
