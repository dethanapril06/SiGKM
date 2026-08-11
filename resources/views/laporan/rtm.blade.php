@extends('layouts.app')

@section('content')
    @php
        $isFakultas = $jenis === 'fakultas';
        $title = $isFakultas ? 'Laporan RTM Fakultas' : 'Laporan RTM Prodi';
        $route = $isFakultas ? route('laporan.rtm.fakultas') : route('laporan.rtm.prodi');
        $exportRoute = $isFakultas ? route('laporan.rtm.fakultas.excel') : route('laporan.rtm.prodi.excel');
        $totalIndikator = $keputusanRtm->count();
        $totalWithRtm = $keputusanRtm->filter(fn ($item) => $item->has_rtm)->count();
        $done = $keputusanRtm->filter(fn ($item) => $item->has_rtm && in_array($item->status, ['selesai', 'ditutup']))->count();
        $process = $keputusanRtm->filter(fn ($item) => $item->has_rtm && in_array($item->status, ['proses', 'dalam_proses']))->count();
        $pending = $totalWithRtm - $done - $process;
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 py-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">{{ $title }}</h4>
            <span class="text-muted">Data memuat seluruh indikator pada semester terpilih. Indikator tanpa keputusan RTM ditandai dengan "-".</span>
        </div>
        <button type="submit" form="laporan-rtm-filter" formaction="{{ $exportRoute }}" class="btn btn-success">
            <i class="bx bx-spreadsheet me-1"></i> Unduh Excel
        </button>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form id="laporan-rtm-filter" method="GET" action="{{ $route }}">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-10 col-md-8">
                        <label for="semester_id" class="form-label">Semester RTM</label>
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

    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="fw-semibold d-block mb-1">Total Indikator</span>
                    <h3 class="card-title mb-2">{{ $totalIndikator }}</h3>
                    <small class="text-muted">{{ $totalWithRtm }} memiliki keputusan / RTL</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="fw-semibold d-block mb-1">Selesai</span>
                    <h3 class="card-title mb-2">{{ $done }}</h3>
                    <small class="text-muted">Keputusan tuntas</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="fw-semibold d-block mb-1">Proses</span>
                    <h3 class="card-title mb-2">{{ $process }}</h3>
                    <small class="text-muted">Masih berjalan</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="fw-semibold d-block mb-1">Belum Dikerjakan</span>
                    <h3 class="card-title mb-2">{{ $pending }}</h3>
                    <small class="text-muted">Perlu tindak lanjut</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="mb-1">Pratinjau Data Excel</h5>
                <small class="text-muted">{{ $isFakultas ? $fakultas : 'Program Studi '.$programStudi }}</small>
            </div>
            <span class="badge bg-label-primary">{{ $totalIndikator }} baris</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">No</th>
                        @if ($isFakultas)
                            <th>Standar</th>
                            <th>Indikator</th>
                        @else
                            <th>Sasaran Strategis</th>
                            <th>IKU</th>
                            <th>IKK</th>
                            <th>IKKS</th>
                        @endif
                        <th>Temuan</th>
                        <th>Risiko</th>
                        <th>Dampak</th>
                        <th>Peringkat</th>
                        <th>Keputusan RTM</th>
                        <th>Tindak Lanjut</th>
                        <th>Strategi</th>
                        <th>Penanggung Jawab</th>
                        <th>Target Selesai</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($keputusanRtm as $item)
                        @php
                            $statusClass = match ($item->status) {
                                'selesai', 'ditutup' => 'bg-label-success',
                                'proses', 'dalam_proses' => 'bg-label-warning',
                                'belum_dikerjakan', 'belum_tercapai', 'terbuka' => 'bg-label-info',
                                default => 'bg-label-secondary',
                            };
                        @endphp
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            @if ($isFakultas)
                                <td style="min-width: 220px; white-space: normal;">
                                    <strong>{{ $item->standar_kode }}</strong>
                                    <small class="d-block text-muted">{{ $item->standar_nama }}</small>
                                </td>
                                <td style="min-width: 300px; white-space: normal;">
                                    <span class="badge bg-label-primary">{{ $item->indikator_kode }}</span>
                                    <span class="d-block mt-1">{{ $item->indikator_isi }}</span>
                                </td>
                            @else
                                <td style="min-width: 240px; white-space: normal;">
                                    <strong>{{ $item->sasaran_kode }}</strong>
                                    <span class="d-block text-muted">{{ $item->sasaran_uraian }}</span>
                                </td>
                                <td style="min-width: 240px; white-space: normal;">
                                    <strong>{{ $item->iku_kode }}</strong>
                                    <span class="d-block text-muted">{{ $item->iku_uraian }}</span>
                                </td>
                                <td style="min-width: 240px; white-space: normal;">
                                    <strong>{{ $item->ikk_kode }}</strong>
                                    <span class="d-block text-muted">{{ $item->ikk_uraian }}</span>
                                </td>
                                <td style="min-width: 240px; white-space: normal;">
                                    <strong>{{ $item->ikks_kode }}</strong>
                                    <span class="d-block text-muted">{{ $item->ikks_uraian }}</span>
                                </td>
                            @endif
                            <td style="min-width: 260px; white-space: normal;">{{ $item->temuan }}</td>
                            <td style="min-width: 220px; white-space: normal;">{{ $item->risiko }}</td>
                            <td style="min-width: 220px; white-space: normal;">{{ $item->dampak }}</td>
                            <td style="min-width: 160px; white-space: normal;">{{ $item->peringkat }}</td>
                            <td style="min-width: 260px; white-space: normal;">{{ $item->keputusan_rtm }}</td>
                            <td style="min-width: 260px; white-space: normal;">{{ $item->tindak_lanjut }}</td>
                            <td style="min-width: 220px; white-space: normal;">{{ $item->strategi }}</td>
                            <td style="min-width: 180px; white-space: normal;">{{ $item->penanggung_jawab }}</td>
                            <td style="min-width: 150px; white-space: normal;">{{ $item->target_selesai }}</td>
                            <td>
                                @if (! $item->has_rtm)
                                    <span class="badge bg-label-secondary">-</span>
                                @else
                                    <span class="badge {{ $statusClass }}">
                                        {{ str($item->status)->replace('_', ' ')->title() }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isFakultas ? 14 : 16 }}" class="text-center py-5 text-muted">
                                Data indikator {{ $isFakultas ? 'fakultas' : 'prodi' }} belum tersedia pada semester ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
