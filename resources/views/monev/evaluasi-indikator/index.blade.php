@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Evaluasi Indikator</h4>

        @if (auth()->user()->hasAnyRole(['ketua-gkm', 'anggota-gkm']))
            <a href="{{ route('evaluasi-indikator.create') }}" class="btn btn-primary">
                <i class="bx bx-plus"></i> Tambah Evaluasi
            </a>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('evaluasi-indikator.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="semester_id" class="form-label">Semester</label>
                        <select id="semester_id" name="semester_id" class="form-select select2">
                            <option value="">-- Semua Semester --</option>
                            @foreach ($semesters as $semester)
                                <option value="{{ $semester->id }}" @selected($selectedSemester == $semester->id)>
                                    {{ $semester->tahunAkademik->nama ?? '-' }} - {{ ucfirst($semester->nama) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="status_capaian" class="form-label">Status Capaian</label>
                        <select id="status_capaian" name="status_capaian" class="form-select">
                            <option value="">-- Semua Status --</option>
                            <option value="tercapai" @selected($selectedStatusCapaian === 'tercapai')>Tercapai</option>
                            <option value="dalam_proses" @selected($selectedStatusCapaian === 'dalam_proses')>Dalam Proses</option>
                            <option value="belum_tercapai" @selected($selectedStatusCapaian === 'belum_tercapai')>Belum Tercapai</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-filter-alt me-1"></i> Filter
                        </button>
                        @if ($selectedSemester || $selectedStatusCapaian)
                            <a href="{{ route('evaluasi-indikator.index') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-reset me-1"></i> Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <h5 class="card-header">Data Evaluasi Indikator</h5>

        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Semester</th>
                        <th style="min-width: 380px;">Sumber Indikator</th>
                        <th>Penanggung Jawab</th>
                        <th>Status Capaian</th>
                        <th>Pembuat</th>
                        <th width="180">Aksi</th>
                    </tr>
                </thead>

                <tbody class="table-border-bottom-0">
                    @forelse($evaluasiIndikator as $item)
                        <tr>
                            <td>{{ $evaluasiIndikator->firstItem() + $loop->index }}</td>
                            <td>
                                <strong>{{ $item->semester->tahunAkademik->nama ?? '-' }}</strong>
                                <br>
                                <small class="text-muted">{{ ucfirst($item->semester->nama ?? '-') }}</small>
                            </td>
                            <td style="min-width: 380px; max-width: 500px; white-space: normal;">
                                <span class="badge bg-label-primary">
                                    {{ $item->sumber_kode }}
                                </span>
                                <br>
                                <strong>{{ $item->sumber_uraian }}</strong>
                                <br>
                                <small class="text-muted">
                                    {{ $item->sumber_jenis }} · {{ $item->sumber_konteks }}
                                </small>
                            </td>
                            <td>{{ $item->nama_penanggung_jawab ?: '-' }}</td>
                            <td>
                                @if ($item->status_capaian === 'tercapai')
                                    <span class="badge bg-label-success">Tercapai</span>
                                @elseif ($item->status_capaian === 'dalam_proses')
                                    <span class="badge bg-label-warning">Dalam Proses</span>
                                @else
                                    <span class="badge bg-label-danger">Belum Tercapai</span>
                                @endif
                            </td>
                            <td>{{ $item->penginput->name ?? '-' }}</td>
                            <td>
                                <a href="{{ route('evaluasi-indikator.show', $item) }}" class="btn btn-sm btn-icon btn-info"><i class="bx bx-show"></i></a>
                                @if ($item->bukti_capaian)
                                    <a href="{{ asset('storage/' . $item->bukti_capaian) }}" target="_blank"
                                        class="btn btn-sm btn-info">
                                        <i class="bx bx-file"></i>
                                    </a>
                                @endif

                                @if (auth()->user()->hasAnyRole(['ketua-gkm', 'anggota-gkm']))
                                    <a href="{{ route('evaluasi-indikator.edit', $item->id) }}"
                                        class="btn btn-sm btn-icon btn-warning">
                                        <i class="bx bx-edit"></i>
                                    </a>

                                    <form action="{{ route('evaluasi-indikator.destroy', $item->id) }}" method="POST"
                                        class="d-inline" data-confirm-form
                                        data-confirm-title="Yakin ingin menghapus evaluasi indikator ini?"
                                        data-confirm-text="Data evaluasi indikator yang dihapus tidak dapat dikembalikan."
                                        data-confirm-button-text="Ya, hapus" data-confirm-button-color="#ff3e1d">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-sm btn-icon btn-danger">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">
                                Data evaluasi indikator belum tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
<div class="mt-3">@include('components._pagination', ['paginator' => $evaluasiIndikator])</div>
@endsection
