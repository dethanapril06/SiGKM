@extends('layouts.app')

@section('content')
    <div class="py-3 mb-4">
        <h4 class="fw-bold mb-1">Pencarian Data Global</h4>
        <p class="text-muted mb-0">Hasil pencarian untuk kata kunci: <strong class="text-primary">"{{ $query }}"</strong></p>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('global-search') }}" method="GET" class="row g-3 align-items-center">
                <div class="col-md-10">
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="bx bx-search fs-4"></i></span>
                        <input type="text" name="q" class="form-control form-control-lg"
                            placeholder="Cari berdasarkan MK, Dosen, Indikator, Temuan, RTL, RTM..."
                            value="{{ $query }}" required autofocus>
                    </div>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bx bx-search me-1"></i> Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if (blank($query))
        <div class="alert alert-info">
            <i class="bx bx-info-circle me-1"></i> Masukkan kata kunci pencarian pada kolom di atas (minimal 2 karakter).
        </div>
    @elseif ($totalResults === 0)
        <div class="alert alert-warning text-center py-5">
            <i class="bx bx-search-alt fs-1 d-block mb-3 text-warning"></i>
            <h5>Tidak Ada Hasil Ditemukan</h5>
            <p class="text-muted mb-0">Tidak dapat menemukan data yang cocok dengan kata kunci <strong>"{{ $query }}"</strong>.</p>
        </div>
    @else
        <div class="row">
            {{-- Ringkasan Perkuliahan --}}
            @if ($ringkasan->isNotEmpty())
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bx bx-book-content text-primary me-2"></i>Ringkasan Perkuliahan</h5>
                            <span class="badge bg-label-primary">{{ $ringkasan->count() }} data</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Jadwal / Semester</th>
                                        <th>Mata Kuliah / Kelas</th>
                                        <th>Keterangan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($ringkasan as $item)
                                        <tr>
                                            <td>
                                                <strong>{{ $item->jadwalMonev->termin->nama_termin ?? '-' }}</strong><br>
                                                <small class="text-muted">{{ $item->jadwalMonev->semester->label ?? '-' }}</small>
                                            </td>
                                            <td>
                                                <strong>{{ $item->perkuliahan->mataKuliah->kode_mk ?? '' }} — {{ $item->perkuliahan->mataKuliah->nama_mk ?? '-' }}</strong><br>
                                                <small class="text-muted">Kelas {{ $item->perkuliahan->kelas->nama_kelas ?? '-' }}</small>
                                            </td>
                                            <td style="max-width: 300px; white-space: normal;">
                                                {{ \Illuminate\Support\Str::limit($item->keterangan ?? '-', 100) }}
                                            </td>
                                            <td>
                                                <a href="{{ route('ringkasan-perkuliahan.show', $item) }}" class="btn btn-sm btn-info">
                                                    <i class="bx bx-show"></i> Detail
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Evaluasi Indikator --}}
            @if ($evaluasi->isNotEmpty())
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bx bx-bar-chart-alt-2 text-info me-2"></i>Evaluasi Indikator</h5>
                            <span class="badge bg-label-info">{{ $evaluasi->count() }} data</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Semester</th>
                                        <th>Indikator</th>
                                        <th>Penanggung Jawab</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($evaluasi as $item)
                                        <tr>
                                            <td>{{ $item->semester->label ?? '-' }}</td>
                                            <td style="max-width: 400px; white-space: normal;">
                                                <span class="badge bg-label-primary">{{ $item->sumber_kode }}</span>
                                                <strong class="d-block mt-1">{{ $item->sumber_uraian }}</strong>
                                            </td>
                                            <td>{{ $item->nama_penanggung_jawab ?: '-' }}</td>
                                            <td>
                                                <a href="{{ route('evaluasi-indikator.show', $item) }}" class="btn btn-sm btn-info">
                                                    <i class="bx bx-show"></i> Detail
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Temuan Evaluasi --}}
            @if ($temuan->isNotEmpty())
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bx bx-error-circle text-danger me-2"></i>Temuan Evaluasi</h5>
                            <span class="badge bg-label-danger">{{ $temuan->count() }} data</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Pernyataan Temuan</th>
                                        <th>Semester</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($temuan as $item)
                                        <tr>
                                            <td><span class="badge bg-label-primary">{{ $item->kode_temuan }}</span></td>
                                            <td style="max-width: 400px; white-space: normal;">{{ \Illuminate\Support\Str::limit($item->pernyataan, 120) }}</td>
                                            <td>{{ $item->evaluasiIndikator->semester->label ?? '-' }}</td>
                                            <td>
                                                <span class="badge bg-label-{{ $item->status === 'ditutup' ? 'success' : 'warning' }}">
                                                    {{ ucfirst($item->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('temuan-evaluasi.show', $item) }}" class="btn btn-sm btn-info">
                                                    <i class="bx bx-show"></i> Detail
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Realisasi RTL --}}
            @if ($rtl->isNotEmpty())
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bx bx-task text-success me-2"></i>Realisasi RTL</h5>
                            <span class="badge bg-label-success">{{ $rtl->count() }} data</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Kode Temuan</th>
                                        <th>Uraian Realisasi</th>
                                        <th>Catatan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rtl as $item)
                                        <tr>
                                            <td><strong>{{ $item->temuan->kode_temuan ?? '-' }}</strong></td>
                                            <td style="max-width: 380px; white-space: normal;">{{ \Illuminate\Support\Str::limit($item->uraian_realisasi, 120) }}</td>
                                            <td>{{ \Illuminate\Support\Str::limit($item->catatan ?? '-', 60) }}</td>
                                            <td>
                                                <a href="{{ route('rtl.show', $item) }}" class="btn btn-sm btn-info">
                                                    <i class="bx bx-show"></i> Detail
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Jadwal RTM --}}
            @if ($rtm->isNotEmpty())
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bx bx-group text-warning me-2"></i>RTM (Rapat Tinjauan Manajemen)</h5>
                            <span class="badge bg-label-warning">{{ $rtm->count() }} data</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Judul RTM</th>
                                        <th>Semester</th>
                                        <th>Tanggal</th>
                                        <th>Tempat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rtm as $item)
                                        <tr>
                                            <td><strong>{{ $item->judul }}</strong></td>
                                            <td>{{ $item->semester->label ?? '-' }}</td>
                                            <td>{{ $item->tanggal?->format('d-m-Y') }}</td>
                                            <td>{{ $item->lokasi ?: '-' }}</td>
                                            <td>
                                                <a href="{{ route('jadwal-rtm.show', $item) }}" class="btn btn-sm btn-info">
                                                    <i class="bx bx-show"></i> Detail
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- AMI --}}
            @if ($ami->isNotEmpty())
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bx bx-folder text-secondary me-2"></i>Audit Mutu Internal (AMI)</h5>
                            <span class="badge bg-label-secondary">{{ $ami->count() }} data</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Tahun Akademik</th>
                                        <th>Tanggal Pelaksanaan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($ami as $item)
                                        <tr>
                                            <td><strong>AMI {{ $item->tahunAkademik?->nama }}</strong></td>
                                            <td>{{ $item->tanggal_pelaksanaan?->format('d-m-Y') }}</td>
                                            <td>
                                                <a href="{{ route('ami.show', $item) }}" class="btn btn-sm btn-info">
                                                    <i class="bx bx-show"></i> Detail
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif
@endsection
