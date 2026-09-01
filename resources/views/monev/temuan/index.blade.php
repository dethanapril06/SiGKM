@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">{{ $judul ?? 'Temuan Evaluasi' }}</h4>

        @if (auth()->user()->hasAnyRole(['ketua-gkm', 'anggota-gkm']))
            @php
                $currentScope = ($activeRoute ?? '') === 'temuan-evaluasi.prodi' ? 'prodi' : 'fakultas';
            @endphp
            <a href="{{ route('temuan-evaluasi.create', ['scope' => $currentScope]) }}" class="btn btn-primary">
                <i class="bx bx-plus"></i> Tambah Temuan
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
            <form method="GET" action="{{ route($activeRoute ?? 'temuan-evaluasi.fakultas') }}">
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
                        <label for="tingkat_risiko_id" class="form-label">Tingkat Risiko</label>
                        <select id="tingkat_risiko_id" name="tingkat_risiko_id" class="form-select">
                            <option value="">-- Semua Risiko --</option>
                            @foreach ($tingkatRisikos ?? [] as $risiko)
                                <option value="{{ $risiko->id }}" @selected(($selectedRisiko ?? null) == $risiko->id)>
                                    {{ $risiko->nama_tingkat }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-filter-alt me-1"></i> Filter
                        </button>
                        @if (($selectedSemester ?? null) || ($selectedRisiko ?? null))
                            <a href="{{ route($activeRoute ?? 'temuan-evaluasi.fakultas') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-reset me-1"></i> Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <h5 class="card-header">Data {{ $judul ?? 'Temuan Evaluasi' }}</h5>

        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Semester</th>
                        <th>Indikator</th>
                        <th>Temuan</th>
                        <th>Risiko</th>
                        <th>Target Selesai</th>
                        <th>Status</th>
                        <th width="180">Aksi</th>
                    </tr>
                </thead>

                <tbody class="table-border-bottom-0">
                    @forelse($temuanEvaluasi as $item)
                        @php
                            $risiko = $item->risikoTemuans->first();
                            $statusClass = match ($item->status) {
                                'draft' => 'bg-label-secondary',
                                'terbuka' => 'bg-label-warning',
                                'ditutup' => 'bg-label-primary',
                                default => 'bg-label-dark',
                            };
                        @endphp

                        <tr>
                            <td>{{ $temuanEvaluasi->firstItem() + $loop->index }}</td>
                            <td>
                                <span class="badge bg-label-primary">{{ $item->kode_temuan }}</span>
                            </td>
                            <td>
                                <strong>{{ $item->evaluasiIndikator->semester->nama ?? '-' }}</strong>
                                <br>
                                <small class="text-muted">
                                    {{ $item->evaluasiIndikator->semester->tahunAkademik->nama ?? '-' }}
                                </small>
                            </td>
                            <td>
                                <strong>{{ $item->evaluasiIndikator->sumber_kode }}</strong>
                                <br>
                                <small class="text-muted">
                                    {{ \Illuminate\Support\Str::limit($item->evaluasiIndikator->sumber_uraian, 55) }}
                                </small>
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($item->pernyataan, 70) }}</td>
                            <td>
                                @if ($risiko)
                                    <span class="badge bg-label-danger">
                                        {{ $risiko->tingkatRisiko->nama_tingkat ?? '-' }}
                                    </span>
                                    <br>
                                    <small class="text-muted">
                                        {{ \Illuminate\Support\Str::limit($risiko->deskripsi_risiko, 45) }}
                                    </small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $item->target_selesai ?: '-' }}</td>
                            <td>
                                <span class="badge {{ $statusClass }}">
                                    {{ ucwords(str_replace('_', ' ', $item->status)) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('temuan-evaluasi.show', $item) }}" class="btn btn-sm btn-icon btn-info"><i
                                        class="bx bx-show"></i></a>
                                @if ($item->canBeEditedBy(auth()->user()))
                                    <a href="{{ route('temuan-evaluasi.edit', $item->id) }}"
                                        class="btn btn-sm btn-icon btn-warning">
                                        <i class="bx bx-edit"></i>
                                    </a>

                                    <form action="{{ route('temuan-evaluasi.destroy', $item->id) }}" method="POST"
                                        class="d-inline" data-confirm-form data-confirm-title="Hapus temuan?"
                                        data-confirm-text="Data temuan dan risiko terkait akan dihapus."
                                        data-confirm-icon="warning" data-confirm-button-text="Ya, hapus"
                                        data-confirm-button-color="#ff3e1d">
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
                            <td colspan="9" class="text-center">
                                Data temuan evaluasi belum tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">@include('components._pagination', ['paginator' => $temuanEvaluasi])</div>
@endsection
