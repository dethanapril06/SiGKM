@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">{{ $judul ?? 'Realisasi Rencana Tindak Lanjut (RTL)' }}</h4>

        @if (auth()->user()->hasAnyRole(['ketua-gkm', 'anggota-gkm']))
            @php
                $currentScope = ($activeRoute ?? '') === 'rtl.prodi' ? 'prodi' : 'fakultas';
            @endphp
            <a href="{{ route('rtl.create', ['scope' => $currentScope]) }}" class="btn btn-primary">
                <i class="bx bx-plus"></i> Catat Realisasi RTL
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
            <form method="GET" action="{{ route($activeRoute ?? 'rtl.fakultas') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="semester_id" class="form-label">Semester</label>
                        <select id="semester_id" name="semester_id" class="form-select select2">
                            <option value="">-- Semua Semester --</option>
                            @foreach ($semesters ?? [] as $semester)
                                <option value="{{ $semester->id }}" @selected(($selectedSemester ?? null) == $semester->id)>
                                    {{ $semester->tahunAkademik->nama ?? '-' }} - {{ ucfirst($semester->nama) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status Temuan</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">-- Semua Status --</option>
                            <option value="terbuka" @selected(($selectedStatus ?? null) === 'terbuka')>Terbuka</option>
                            <option value="ditutup" @selected(($selectedStatus ?? null) === 'ditutup')>Selesai / Ditutup</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-filter-alt me-1"></i> Filter
                        </button>
                        @if (($selectedSemester ?? null) || ($selectedStatus ?? null))
                            <a href="{{ route($activeRoute ?? 'rtl.fakultas') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-reset me-1"></i> Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <h5 class="card-header">Data Realisasi {{ $judul ?? 'RTL' }}</h5>

        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Temuan & RTL Awal</th>
                        <th>Semester</th>
                        <th>Uraian Realisasi</th>
                        <th>Waktu Pelaksanaan</th>
                        <th>Bukti</th>
                        <th>Status Temuan</th>
                        <th width="140">Aksi</th>
                    </tr>
                </thead>

                <tbody class="table-border-bottom-0">
                    @forelse($rtl as $item)
                        <tr>
                            <td>{{ $rtl->firstItem() + $loop->index }}</td>
                            <td>
                                <strong>{{ $item->temuan->kode_temuan ?? '-' }}</strong>
                                <br>
                                <small class="text-muted">
                                    Rencana: {{ \Illuminate\Support\Str::limit($item->temuan->rencana_awal ?? '-', 50) }}
                                </small>
                            </td>
                            <td>
                                <strong>{{ $item->temuan->evaluasiIndikator->semester->nama ?? '-' }}</strong>
                                <br>
                                <small class="text-muted">
                                    {{ $item->temuan->evaluasiIndikator->semester->tahunAkademik->nama ?? '-' }}
                                </small>
                            </td>
                            <td>
                                <strong>{{ \Illuminate\Support\Str::limit($item->uraian_realisasi ?? '-', 60) }}</strong>
                                @if ($item->penanggung_jawab)
                                    <br>
                                    <small class="text-primary">
                                        PJ: {{ $item->penanggung_jawab }}
                                    </small>
                                @endif
                                @if ($item->catatan)
                                    <br>
                                    <small class="text-muted">
                                        Catatan: {{ \Illuminate\Support\Str::limit($item->catatan, 50) }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                {{ $item->waktu_pelaksanaan?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td>
                                @forelse ($item->buktiTindakLanjuts as $bukti)
                                    <a href="{{ asset('storage/' . $bukti->file_path) }}" target="_blank"
                                        class="btn btn-sm btn-outline-primary mb-1">
                                        <i class="bx bx-file"></i> Bukti {{ $loop->iteration }}
                                    </a>
                                @empty
                                    <span class="text-muted">-</span>
                                @endforelse
                            </td>
                            <td>
                                @if (($item->temuan->status ?? '') === 'ditutup')
                                    <span class="badge bg-label-success">Selesai / Ditutup</span>
                                @else
                                    <span class="badge bg-label-warning">Terbuka</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('rtl.show', $item) }}" class="btn btn-sm btn-icon btn-info"><i
                                        class="bx bx-show"></i></a>
                                @if ($item->canBeEditedBy(auth()->user()))
                                    <a href="{{ route('rtl.edit', $item->id) }}" class="btn btn-sm btn-icon btn-warning">
                                        <i class="bx bx-edit"></i>
                                    </a>

                                    <form action="{{ route('rtl.destroy', $item->id) }}" method="POST" class="d-inline"
                                        data-confirm-form data-confirm-title="Hapus Realisasi RTL?"
                                        data-confirm-text="Data realisasi RTL akan dihapus permanen dan status Temuan akan kembali Terbuka."
                                        data-confirm-icon="warning"
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
                            <td colspan="8" class="text-center">
                                Data realisasi RTL belum tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">@include('components._pagination', ['paginator' => $rtl])</div>
@endsection
