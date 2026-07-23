@extends('layouts.app')

@section('content')
    @php
        $isFakultas = $jenis === 'fakultas';
        $title = $isFakultas ? 'Laporan RTL Fakultas' : 'Laporan RTL Prodi';
        $route = $isFakultas ? route('laporan.rtl.fakultas') : route('laporan.rtl.prodi');
        $exportRoute = $isFakultas ? route('laporan.rtl.fakultas.excel') : route('laporan.rtl.prodi.excel');
        $closed = $rtl->filter(fn ($item) => ($item->temuan?->status ?? '') === 'ditutup')->count();
        $open = $rtl->count() - $closed;
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 py-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">{{ $title }}</h4>
            <span class="text-muted">Data diambil dari realisasi RTL pada semester terpilih.</span>
        </div>
        <button type="submit" form="laporan-rtl-filter" formaction="{{ $exportRoute }}" class="btn btn-success">
            <i class="bx bx-spreadsheet me-1"></i> Unduh Excel
        </button>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form id="laporan-rtl-filter" method="GET" action="{{ $route }}">
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

    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="fw-semibold d-block mb-1">Total RTL</span>
                    <h3 class="card-title mb-2">{{ $rtl->count() }}</h3>
                    <small class="text-muted">{{ $selectedSemester?->label ?? 'Semester belum tersedia' }}</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="fw-semibold d-block mb-1">Selesai</span>
                    <h3 class="card-title mb-2">{{ $closed }}</h3>
                    <small class="text-muted">Temuan ditutup</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="fw-semibold d-block mb-1">Terbuka</span>
                    <h3 class="card-title mb-2">{{ $open }}</h3>
                    <small class="text-muted">Temuan belum ditutup</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="fw-semibold d-block mb-1">Dengan Bukti</span>
                    <h3 class="card-title mb-2">{{ $rtl->filter(fn ($item) => $item->buktiTindakLanjuts->isNotEmpty())->count() }}</h3>
                    <small class="text-muted">Bukti terlampir</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="mb-1">Pratinjau Data Excel</h5>
                <small class="text-muted">
                    {{ $isFakultas ? $fakultas : 'Program Studi '.$programStudi }}
                </small>
            </div>
            <span class="badge bg-label-primary">{{ $rtl->count() }} data</span>
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
                        <th>Tindak Lanjut</th>
                        <th>Penanggung Jawab</th>
                        <th>Target Waktu</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rtl as $item)
                        @php
                            $evaluatable = $item->temuan?->evaluasiIndikator?->evaluatable;
                            $ikk = $isFakultas ? null : $evaluatable?->indikatorKinerjaKegiatan;
                            $iku = $ikk?->indikatorKinerjaUtama;
                            $sasaran = $iku?->sasaranStrategis;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            @if ($isFakultas)
                                <td style="min-width: 220px; white-space: normal;">
                                    <strong>{{ $evaluatable->standarMutu->kode_standar ?? '-' }}</strong>
                                    <small class="d-block text-muted">{{ $evaluatable->standarMutu->nama_standar ?? '-' }}</small>
                                </td>
                                <td style="min-width: 300px; white-space: normal;">
                                    <span class="badge bg-label-primary">{{ $evaluatable->kode_indikator ?? '-' }}</span>
                                    <span class="d-block mt-1">{{ $evaluatable->isi_indikator ?? '-' }}</span>
                                </td>
                            @else
                                <td style="min-width: 240px; white-space: normal;">
                                    <strong>{{ $sasaran?->kode_sasaran ?? '-' }}</strong>
                                    <span class="d-block text-muted">{{ $sasaran?->uraian_sasaran ?? '-' }}</span>
                                </td>
                                <td style="min-width: 240px; white-space: normal;">
                                    <strong>{{ $iku?->kode_iku ?? '-' }}</strong>
                                    <span class="d-block text-muted">{{ $iku?->uraian_iku ?? '-' }}</span>
                                </td>
                                <td style="min-width: 240px; white-space: normal;">
                                    <strong>{{ $ikk?->kode_ikk ?? '-' }}</strong>
                                    <span class="d-block text-muted">{{ $ikk?->uraian_ikk ?? '-' }}</span>
                                </td>
                                <td style="min-width: 240px; white-space: normal;">
                                    <strong>{{ $evaluatable->kode_ikks ?? '-' }}</strong>
                                    <span class="d-block text-muted">{{ $evaluatable->uraian_ikks ?? '-' }}</span>
                                </td>
                            @endif
                            <td style="min-width: 260px; white-space: normal;">{{ $item->temuan?->pernyataan ?? '-' }}</td>
                            <td style="min-width: 260px; white-space: normal;">
                                <strong>Rencana:</strong> {{ $item->temuan?->rencana_awal ?? '-' }}<br>
                                <strong class="mt-1 d-block">Realisasi:</strong> {{ $item->uraian_realisasi ?? '-' }}
                            </td>
                            <td style="min-width: 180px; white-space: normal;">{{ $item->temuan?->nama_penanggung_jawab ?? '-' }}</td>
                            <td style="min-width: 150px; white-space: normal;">{{ $item->temuan?->target_selesai?->translatedFormat('d M Y') ?? '-' }}</td>
                            <td>
                                @if (($item->temuan?->status ?? '') === 'ditutup')
                                    <span class="badge bg-label-success">Selesai / Ditutup</span>
                                @else
                                    <span class="badge bg-label-warning">Terbuka</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isFakultas ? 9 : 12 }}" class="text-center py-5 text-muted">
                                Data RTL {{ $isFakultas ? 'fakultas' : 'prodi' }} belum tersedia pada semester ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
