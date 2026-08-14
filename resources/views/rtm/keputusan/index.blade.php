@extends('layouts.app')
@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">{{ $judul ?? 'Keputusan RTM' }}</h4>
        @if (auth()->user()->hasAnyRole(['ketua-gkm', 'anggota-gkm']))
            <a href="{{ route('keputusan-rtm.create') }}" class="btn btn-primary">
                <i class="bx bx-plus"></i> Tambah Keputusan
            </a>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route($activeRoute ?? 'keputusan-rtm.fakultas') }}">
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
                            <a href="{{ route($activeRoute ?? 'keputusan-rtm.fakultas') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-reset me-1"></i> Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <h5 class="card-header">Data {{ $judul ?? 'Keputusan RTM' }}</h5>
        <div class="table-responsive text-nowrap">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Semester & RTM</th>
                        <th>Temuan yang Ditinjau</th>
                        <th>Keputusan</th>
                        <th>Strategi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($keputusanRtm as $item)
                        <tr>
                            <td>{{ $keputusanRtm->firstItem() + $loop->index }}</td>
                            <td>
                                <strong>{{ $item->semester?->label ?? $item->notulenRtm?->jadwalRtm?->semester?->label ?? $item->temuan?->evaluasiIndikator?->semester?->label ?? '-' }}</strong>
                                <br>
                                @if ($item->notulenRtm)
                                    <small class="text-primary">
                                        <i class="bx bx-calendar-event me-1"></i>{{ $item->notulenRtm->jadwalRtm?->judul ?? 'RTM' }}
                                    </small>
                                @else
                                    <span class="badge bg-label-secondary mt-1">
                                        <i class="bx bx-minus me-1"></i>Tanpa RTM
                                    </span>
                                @endif
                            </td>
                            <td style="min-width:240px;white-space:normal">
                                <strong>{{ $item->temuan?->kode_temuan }}</strong>
                                @if ($item->temuan?->status)
                                    <span class="badge bg-label-{{ $item->temuan->status === 'ditutup' ? 'success' : ($item->temuan->status === 'terbuka' ? 'warning' : 'secondary') }} ms-1">
                                        {{ str($item->temuan->status)->replace('_', ' ')->title() }}
                                    </span>
                                @endif
                                <br>{{ Str::limit($item->temuan?->pernyataan, 100) }}
                                <br><small class="text-muted">PJ: {{ $item->temuan?->nama_penanggung_jawab ?? '-' }}</small>
                            </td>
                            <td style="min-width:220px;white-space:normal">{{ Str::limit($item->uraian_keputusan, 110) }}</td>
                            <td style="min-width:180px;white-space:normal">{{ Str::limit($item->strategi, 100) ?: '-' }}</td>
                            <td>
                                <a href="{{ route('keputusan-rtm.show', $item) }}" class="btn btn-sm btn-icon btn-info" title="Detail">
                                    <i class="bx bx-show"></i>
                                </a>
                                @if (auth()->user()->hasAnyRole(['ketua-gkm', 'anggota-gkm']))
                                    <a href="{{ route('keputusan-rtm.edit', $item) }}" class="btn btn-sm btn-icon btn-warning" title="Edit">
                                        <i class="bx bx-edit"></i>
                                    </a>
                                    <form action="{{ route('keputusan-rtm.destroy', $item) }}" method="POST" class="d-inline"
                                        data-confirm-form data-confirm-title="Yakin ingin menghapus keputusan ini?"
                                        data-confirm-text="Keputusan RTM yang dihapus tidak dapat dikembalikan."
                                        data-confirm-button-text="Ya, hapus" data-confirm-button-color="#ff3e1d">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-icon btn-danger" title="Hapus">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">Belum ada data keputusan RTM.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">@include('components._pagination', ['paginator' => $keputusanRtm])</div>
@endsection
