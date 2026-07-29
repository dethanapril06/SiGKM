@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Laporan Pencapaian Mutu</h4>
    </div>

    @include('laporan.partials.filter')

    <div class="card mb-4">
        <h5 class="card-header">Rekap Pencapaian Mutu</h5>
        <div class="card-body">
            <p class="mb-0">
                Laporan ini menampilkan hasil evaluasi indikator mutu berdasarkan ringkasan perkuliahan,
                standar mutu, target indikator, nilai capaian, dan status pencapaian.
            </p>
        </div>
    </div>

    @php
        $total = $evaluasiIndikator->count();
        $tercapai = $evaluasiIndikator->where('status_capaian', 'tercapai')->count();
        $hampir = $evaluasiIndikator->where('status_capaian', 'dalam_proses')->count();
        $tidak = $evaluasiIndikator->where('status_capaian', 'tidak_tercapai')->count();
        $tidakDinilai = $evaluasiIndikator->where('status_capaian', 'tidak_dinilai')->count();

        $persenTercapai = $total > 0 ? ($tercapai / $total) * 100 : 0;
    @endphp

    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="fw-semibold d-block mb-1">Total Evaluasi</span>
                    <h3 class="card-title mb-2">{{ $total }}</h3>
                    <small class="text-muted">Indikator dievaluasi</small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="fw-semibold d-block mb-1">Tercapai</span>
                    <h3 class="card-title mb-2">{{ $tercapai }}</h3>
                    <small class="text-muted">{{ number_format($persenTercapai, 2) }}% dari total evaluasi</small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="fw-semibold d-block mb-1">Dalam Proses</span>
                    <h3 class="card-title mb-2">{{ $hampir }}</h3>
                    <small class="text-muted">Perlu perhatian</small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <span class="fw-semibold d-block mb-1">Tidak Tercapai</span>
                    <h3 class="card-title mb-2">{{ $tidak }}</h3>
                    <small class="text-muted">Perlu tindak lanjut</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <h5 class="card-header">Ringkasan Berdasarkan Standar Mutu</h5>

        <div class="table-responsive text-nowrap">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Standar Mutu</th>
                        <th>Total Indikator</th>
                        <th>Tercapai</th>
                        <th>Dalam Proses</th>
                        <th>Tidak Tercapai</th>
                        <th>Tidak Dinilai</th>
                    </tr>
                </thead>

                <tbody>
                    @php
                        $groupStandar = $evaluasiIndikator->groupBy(function ($item) {
                            return $item->indikatorMutu->standarMutu->nama_standar ?? 'Tanpa Standar';
                        });
                    @endphp

                    @forelse($groupStandar as $namaStandar => $items)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $namaStandar }}</strong>
                            </td>
                            <td>{{ $items->count() }}</td>
                            <td>{{ $items->where('status_capaian', 'tercapai')->count() }}</td>
                            <td>{{ $items->where('status_capaian', 'dalam_proses')->count() }}</td>
                            <td>{{ $items->where('status_capaian', 'tidak_tercapai')->count() }}</td>
                            <td>{{ $items->where('status_capaian', 'tidak_dinilai')->count() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">
                                Data ringkasan standar mutu belum tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h5 class="card-header">Detail Pencapaian Mutu</h5>

        <div class="table-responsive text-nowrap">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Standar Mutu</th>
                        <th>Indikator</th>
                        <th>Perkuliahan</th>
                        <th>Dosen</th>
                        <th>Kelas</th>
                        <th>Target</th>
                        <th>Nilai Capaian</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($evaluasiIndikator as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>

                            <td>
                                <strong>{{ $item->indikatorMutu->standarMutu->kode_standar ?? '-' }}</strong>
                                <br>
                                <small class="text-muted">
                                    {{ $item->indikatorMutu->standarMutu->nama_standar ?? '-' }}
                                </small>
                            </td>

                            <td>
                                <span class="badge bg-label-primary">
                                    {{ $item->indikatorMutu->kode_indikator ?? '-' }}
                                </span>
                                <br>
                                <strong>{{ $item->indikatorMutu->nama_indikator ?? '-' }}</strong>
                            </td>

                            <td>
                                {{ $item->ringkasanPerkuliahan->mengajar->mataKuliah->nama_mata_kuliah ?? '-' }}
                                <br>
                                <small class="text-muted">
                                    {{ $item->ringkasanPerkuliahan->jadwalMonev->nama_kegiatan ?? '-' }}
                                </small>
                            </td>

                            <td>
                                {{ $item->ringkasanPerkuliahan->mengajar->dosen->nama_dosen ?? '-' }}
                            </td>

                            <td>
                                {{ $item->ringkasanPerkuliahan->mengajar->kelas->nama_kelas ?? '-' }}
                            </td>

                            <td>
                                @if ($item->indikatorMutu && $item->indikatorMutu->target !== null)
                                    {{ $item->indikatorMutu->operator_pembanding }}
                                    {{ $item->indikatorMutu->target }}
                                    {{ $item->indikatorMutu->satuan }}
                                @else
                                    -
                                @endif
                            </td>

                            <td>
                                {{ $item->nilai_capaian ?? '-' }}
                                {{ $item->indikatorMutu->satuan ?? '' }}
                            </td>

                            <td>
                                @if ($item->status_capaian === 'tercapai')
                                    <span class="badge bg-label-success">Tercapai</span>
                                @elseif($item->status_capaian === 'dalam_proses')
                                    <span class="badge bg-label-warning">Dalam Proses</span>
                                @elseif($item->status_capaian === 'tidak_tercapai')
                                    <span class="badge bg-label-danger">Tidak Tercapai</span>
                                @else
                                    <span class="badge bg-label-secondary">Tidak Dinilai</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">
                                Data pencapaian mutu belum tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        @media print {
            aside,
            nav,
            footer,
            .btn,
            .no-print,
            .layout-menu-toggle {
                display: none !important;
            }

            .layout-page,
            .content-wrapper,
            .container-xxl,
            .container-p-y {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            .card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                page-break-inside: avoid;
            }

            body {
                background: #fff !important;
            }
        }
    </style>
@endpush
