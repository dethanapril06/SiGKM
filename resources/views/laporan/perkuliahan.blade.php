@extends('layouts.app')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 py-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Laporan Pelaksanaan Perkuliahan</h4>
            <span class="text-muted">Pilih data laporan, ajukan verifikasi, lalu unduh menggunakan format Excel resmi.</span>
        </div>
        <div class="d-flex gap-2">
            @if ($selectedSemester && $selectedJadwalMonev && $ringkasanPerkuliahan->isNotEmpty())
                @if (!$laporan || in_array($laporan->status, ['draft', 'ditolak']))
                    <form action="{{ route('laporan.perkuliahan.submit') }}" method="POST"
                        data-confirm-form
                        data-confirm-title="{{ $laporan && $laporan->status === 'ditolak' ? 'Ajukan Ulang Laporan Perkuliahan?' : 'Ajukan Laporan Pelaksanaan Perkuliahan?' }}"
                        data-confirm-text="Laporan akan diajukan ke Ketua GKM untuk diverifikasi."
                        data-confirm-button-text="Ya, Ajukan Laporan"
                        data-confirm-icon="question">
                        @csrf
                        <input type="hidden" name="semester_id" value="{{ $selectedSemester->id }}">
                        <input type="hidden" name="jadwal_monev_id" value="{{ $selectedJadwalMonev->id }}">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-paper-plane me-1"></i> {{ $laporan && $laporan->status === 'ditolak' ? 'Ajukan Ulang Laporan' : 'Ajukan Laporan Perkuliahan' }}
                        </button>
                    </form>
                @endif
            @endif
            @if ($laporan && $laporan->status === 'diverifikasi')
                <button type="submit" form="laporan-perkuliahan-filter" formaction="{{ route('laporan.perkuliahan.excel') }}"
                    class="btn btn-success">
                    <i class="bx bx-spreadsheet me-1"></i> Unduh Excel
                </button>
            @else
                <button type="button" class="btn btn-secondary" disabled title="Unduh Excel hanya tersedia jika laporan telah diverifikasi oleh Ketua GKM">
                    <i class="bx bx-lock-alt me-1"></i> Unduh Excel (Belum Diverifikasi)
                </button>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible mb-4" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($missingCount > 0 && $selectedJadwalMonev)
        <div class="alert alert-warning alert-dismissible mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bx bx-error-circle fs-4 me-2"></i>
                <div>
                    <strong>Peringatan Kelengkapan Data:</strong> Masih terdapat <strong>{{ $missingCount }}</strong> dari <strong>{{ $totalPerkuliahanCount }}</strong> Mata Kuliah aktif yang belum diisi ringkasannya pada Pelaksanaan Monev ini.
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($laporan)
        <div class="card mb-4 border-start border-4 {{ $laporan->status === 'diverifikasi' ? 'border-success' : ($laporan->status === 'diajukan' ? 'border-warning' : ($laporan->status === 'ditolak' ? 'border-danger' : 'border-secondary')) }}">
            <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h6 class="mb-1">
                        Status Laporan: 
                        <span class="badge {{ $laporan->status === 'diverifikasi' ? 'bg-label-success' : ($laporan->status === 'diajukan' ? 'bg-label-warning' : ($laporan->status === 'ditolak' ? 'bg-label-danger' : 'bg-label-secondary')) }}">
                            {{ str($laporan->status)->title() }}
                        </span>
                    </h6>
                    @if ($laporan->status === 'diajukan')
                        <small class="text-muted">Laporan telah diajukan oleh <strong>{{ $laporan->pembuat?->name ?? 'Penginput' }}</strong> dan sedang menunggu verifikasi Ketua GKM.</small>
                    @elseif ($laporan->status === 'diverifikasi')
                        <small class="text-muted">Laporan telah diverifikasi oleh <strong>{{ $laporan->verifikator?->name ?? 'Ketua GKM' }}</strong> pada {{ $laporan->verified_at?->translatedFormat('d F Y H:i') }}.</small>
                    @elseif ($laporan->status === 'ditolak')
                        <small class="text-danger font-weight-bold">Catatan Perbaikan: {{ $laporan->catatan_verifikasi }}</small>
                    @endif
                </div>
                @if ($laporan->status === 'diverifikasi' && auth()->user()->hasRole('ketua-gkm'))
                    <form action="{{ route('laporan.batalkan-verifikasi', $laporan) }}" method="POST"
                        data-confirm-form
                        data-confirm-title="Batalkan Verifikasi Laporan?"
                        data-confirm-text="Laporan dan data ringkasan perkuliahan akan dibuka kembali untuk perbaikan."
                        data-confirm-button-text="Ya, Batalkan Verifikasi"
                        data-confirm-icon="warning">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bx bx-undo me-1"></i> Batalkan Verifikasi
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form id="laporan-perkuliahan-filter" method="GET" action="{{ route('laporan.perkuliahan') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-5 col-md-6">
                        <label for="semester_id" class="form-label">Semester</label>
                        <select id="semester_id" name="semester_id" class="form-select select2" onchange="this.form.submit()">
                            @forelse ($semesters as $semester)
                                <option value="{{ $semester->id }}" @selected($selectedSemester?->id === $semester->id)>
                                    {{ $semester->label }}{{ $semester->is_active ? ' (Aktif)' : '' }}
                                </option>
                            @empty
                                <option value="">Belum ada semester</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="col-lg-5 col-md-6">
                        <label for="jadwal_monev_id" class="form-label">Pelaksanaan Monev</label>
                        <select id="jadwal_monev_id" name="jadwal_monev_id" class="form-select select2">
                            @forelse ($jadwalMonevs as $jadwal)
                                <option value="{{ $jadwal->id }}" @selected($selectedJadwalMonev?->id === $jadwal->id)>
                                    {{ $jadwal->termin?->nama_termin ?? 'Tanpa termin' }}
                                    ({{ $jadwal->tanggal_mulai->translatedFormat('d M Y') }})
                                </option>
                            @empty
                                <option value="">Belum ada jadwal monev</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-12 d-grid">
                        <button type="submit" class="btn btn-primary" title="Terapkan filter">
                            <i class="bx bx-filter-alt me-1"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="mb-1">Pratinjau Data Excel</h5>
                <small class="text-muted">
                    {{ $selectedSemester?->label ?? 'Semester belum tersedia' }}
                    @if ($selectedJadwalMonev)
                        · {{ $selectedJadwalMonev->termin?->nama_termin ?? 'Tanpa termin' }}
                    @endif
                </small>
            </div>
            <span class="badge bg-label-primary">{{ $ringkasanPerkuliahan->count() }} data</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">No</th>
                        <th>Mata Kuliah</th>
                        <th class="text-center">Kelas</th>
                        <th>Dosen MK</th>
                        <th class="text-center">Pertemuan</th>
                        <th class="text-center">Kontrak Kuliah</th>
                        <th class="text-center">Kesesuaian</th>
                        <th>Keterangan (Temuan/Masalah)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ringkasanPerkuliahan as $item)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $item->perkuliahan?->mataKuliah?->nama_lengkap ?? '-' }}</strong>
                            </td>
                            <td class="text-center">{{ $item->perkuliahan?->kelas?->nama_kelas ?? '-' }}</td>
                            <td>{{ $item->perkuliahan?->pengajars?->pluck('dosen.nama_dosen')->filter()->join(', ') ?: '-' }}
                            </td>
                            <td class="text-center">{{ $item->jumlah_pertemuan }}</td>
                            <td class="text-center">
                                <span class="badge {{ $item->kontrak_kuliah === 'ada' ? 'bg-label-success' : 'bg-label-danger' }}">
                                    {{ $item->kontrak_kuliah === 'ada' ? 'Ada' : 'Tidak Ada' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span
                                    class="badge {{ $item->kesesuaian_materi === 'sesuai' ? 'bg-label-success' : 'bg-label-warning' }}">
                                    {{ str($item->kesesuaian_materi)->replace('_', ' ')->title() }}
                                </span>
                            </td>
                            <td>{{ $item->keterangan ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                Belum ada ringkasan perkuliahan yang diverifikasi pada pelaksanaan monev ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
