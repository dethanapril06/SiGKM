@extends('layouts.app')
@section('content')
@php
    $canManage = auth()->user()->hasAnyRole(['ketua-gkm', 'anggota-gkm']);
    $fileFields = [
        'file_ami'           => ['label' => 'File AMI',           'icon' => 'bx-file'],
        'file_tindak_lanjut' => ['label' => 'File Tindak Lanjut', 'icon' => 'bx-list-check'],
        'file_dokumentasi'   => ['label' => 'File Dokumentasi',   'icon' => 'bx-folder-open'],
        'file_absensi'       => ['label' => 'File Absensi',       'icon' => 'bx-group'],
    ];
@endphp

<div class="d-flex justify-content-between align-items-center py-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Audit Mutu Internal (AMI)</h4>
        <p class="text-muted mb-0">Rekapan AMI per tahun akademik.</p>
    </div>
    @if ($canManage)
        <a href="{{ route('ami.create') }}" class="btn btn-primary"><i class="bx bx-plus"></i> Tambah AMI</a>
    @endif
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

@forelse($ami as $item)
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">AMI {{ $item->tahunAkademik?->nama }}</h5>
                <small class="text-muted">
                    Tanggal: {{ $item->tanggal_pelaksanaan?->format('d-m-Y') }} ·
                    Diinput oleh {{ $item->penginput?->name ?? '-' }}
                </small>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('ami.show', $item) }}" class="btn btn-sm btn-info">
                    <i class="bx bx-show"></i> Detail
                </a>
                @if ($canManage)
                    <a href="{{ route('ami.edit', $item) }}" class="btn btn-sm btn-warning">
                        <i class="bx bx-edit"></i> Edit
                    </a>
                    <form action="{{ route('ami.destroy', $item) }}" method="POST" class="d-inline"
                        data-confirm-form data-confirm-title="Hapus data AMI?"
                        data-confirm-text="Semua file terkait akan dihapus permanen."
                        data-confirm-icon="warning" data-confirm-button-text="Ya, hapus"
                        data-confirm-button-color="#ff3e1d">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="bx bx-trash"></i> Hapus
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="card-body">
            <div class="row">
                @foreach($fileFields as $field => $meta)
                    <div class="col-md-3 mb-3">
                        <div class="border rounded p-3 h-100 text-center">
                            <i class="bx {{ $meta['icon'] }} fs-3 text-primary mb-2 d-block"></i>
                            <div class="fw-semibold small mb-2">{{ $meta['label'] }}</div>
                            @if($item->$field)
                                <a href="{{ asset('storage/' . $item->$field) }}" target="_blank"
                                    class="btn btn-sm btn-outline-primary">
                                    <i class="bx bx-download"></i> Unduh
                                </a>
                            @else
                                <span class="badge bg-label-secondary">Belum ada</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@empty
    <div class="alert alert-info">Belum ada data AMI.</div>
@endforelse

<div class="mt-3">@include('components._pagination', ['paginator' => $ami])</div>
@endsection
