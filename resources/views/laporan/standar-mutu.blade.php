@extends('layouts.app')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 py-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Evaluasi Standar Mutu Fakultas</h4>
            <span class="text-muted">Pilih semester, lalu unduh laporan menggunakan template Excel resmi.</span>
        </div>
        <button type="submit" form="laporan-standar-mutu-filter" formaction="{{ route('laporan.standar-mutu.excel') }}"
            class="btn btn-success">
            <i class="bx bx-spreadsheet me-1"></i> Unduh Excel
        </button>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form id="laporan-standar-mutu-filter" method="GET" action="{{ route('laporan.standar-mutu') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-10 col-md-8">
                        <label for="semester_id" class="form-label">Semester</label>
                        <select id="semester_id" name="semester_id" class="form-select">
                            @forelse ($semesters as $semester)
                                <option value="{{ $semester->id }}" @selected($selectedSemester?->id === $semester->id)>
                                    {{ $semester->label }}{{ $semester->is_active ? ' (Aktif)' : '' }}
                                </option>
                            @empty
                                <option value="">Belum ada semester</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4 d-grid">
                        <button type="submit" class="btn btn-primary" title="Terapkan filter">
                            <i class="bx bx-filter-alt me-1"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @php
        $evaluasi = $indikatorMutu->map(fn ($item) => $item->evaluasiIndikators->first())->filter();
        $total = $indikatorMutu->count();
        $tercapai = $evaluasi->where('status_capaian', 'tercapai')->count();
        $hampir = $evaluasi->where('status_capaian', 'hampir_tercapai')->count();
        $belum = $evaluasi->where('status_capaian', 'belum_tercapai')->count();
        $denganTemuan = $evaluasi->filter(fn ($item) => $item->temuans->isNotEmpty())->count();
    @endphp

    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="fw-semibold d-block mb-1">Total Evaluasi</span>
                    <h3 class="card-title mb-2">{{ $total }}</h3>
                    <small class="text-muted">Indikator mutu fakultas</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="fw-semibold d-block mb-1">Tercapai</span>
                    <h3 class="card-title mb-2">{{ $tercapai }}</h3>
                    <small class="text-muted">{{ $total > 0 ? number_format(($tercapai / $total) * 100, 2) : '0.00' }}% dari total</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="fw-semibold d-block mb-1">Hampir/Belum</span>
                    <h3 class="card-title mb-2">{{ $hampir + $belum }}</h3>
                    <small class="text-muted">Perlu pemantauan</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="fw-semibold d-block mb-1">Dengan Temuan</span>
                    <h3 class="card-title mb-2">{{ $denganTemuan }}</h3>
                    <small class="text-muted">Memiliki catatan temuan</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="mb-1">Pratinjau Data Excel</h5>
                <small class="text-muted">{{ $selectedSemester?->label ?? 'Semester belum tersedia' }}</small>
            </div>
            <span class="badge bg-label-primary">{{ $indikatorMutu->count() }} data</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">Baris</th>
                        <th>Standar</th>
                        <th class="text-center">Indikator</th>
                        <th>Status</th>
                        <th>Temuan</th>
                        <th>Rencana Perbaikan</th>
                        <th>Target Capaian</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($indikatorMutu as $item)
                        @php
                            $evaluasiItem = $item->evaluasiIndikators->first();
                            $temuanItems = $evaluasiItem?->temuans ?? collect();
                            $temuan = $temuanItems->pluck('pernyataan')->filter();
                            $plans = $temuanItems
                                ->pluck('rencana_awal')
                                ->filter();
                            $targets = $temuanItems
                                ->pluck('target_selesai')
                                ->filter()
                                ->map(fn ($date) => $date->translatedFormat('d M Y'));
                        @endphp
                        <tr>
                            <td class="text-center">{{ 12 + $loop->iteration }}</td>
                            <td style="min-width: 220px; white-space: normal;">
                                <strong>{{ $item->standarMutu->kode_standar ?? '-' }}</strong>
                                <small class="d-block text-muted">{{ $item->standarMutu->nama_standar ?? '-' }}</small>
                            </td>
                            <td style="min-width: 300px; white-space: normal;">
                                <span class="badge bg-label-primary">{{ $item->kode_indikator ?? '-' }}</span>
                                <span class="d-block mt-1">{{ $item->isi_indikator ?? '-' }}</span>
                            </td>
                            <td>
                                @if (! $evaluasiItem)
                                    <span class="badge bg-label-secondary">Belum Dievaluasi</span>
                                @elseif ($evaluasiItem->status_capaian === 'tercapai')
                                    <span class="badge bg-label-success">Tercapai</span>
                                @elseif ($evaluasiItem->status_capaian === 'hampir_tercapai')
                                    <span class="badge bg-label-warning">Hampir Tercapai</span>
                                @else
                                    <span class="badge bg-label-danger">Belum Tercapai</span>
                                @endif
                            </td>
                            <td style="min-width: 240px; white-space: normal;">{{ $temuan->isNotEmpty() ? $temuan->join('; ') : ($evaluasiItem?->status_capaian === 'tercapai' ? 'Tidak ada temuan' : ($evaluasiItem?->catatan ?: '-')) }}</td>
                            <td style="min-width: 240px; white-space: normal;">{{ $plans->isNotEmpty() ? $plans->join('; ') : ($temuanItems->pluck('rencana_awal')->filter()->join('; ') ?: '-') }}</td>
                            <td style="min-width: 160px; white-space: normal;">{{ $targets->isNotEmpty() ? $targets->join('; ') : ($temuanItems->pluck('target_selesai')->filter()->map(fn ($date) => $date->translatedFormat('d M Y'))->join('; ') ?: '-') }}</td>
                            <td style="min-width: 220px; white-space: normal;">{{ $evaluasiItem?->catatan ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                Belum ada indikator mutu fakultas yang aktif di database.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
