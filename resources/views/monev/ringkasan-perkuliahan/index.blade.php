@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Ringkasan Perkuliahan</h4>

        @if (auth()->user()->hasAnyRole(['ketua-gkm', 'anggota-gkm']))
            <a href="{{ route('ringkasan-perkuliahan.create') }}" class="btn btn-primary">
                <i class="bx bx-plus"></i> Tambah Ringkasan
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
            <form method="GET" action="{{ route('ringkasan-perkuliahan.index') }}">
                <div class="row g-3 align-items-end mb-3">
                    <div class="col-md-4">
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
                        <label for="termin_id" class="form-label">Termin Monev</label>
                        <select id="termin_id" name="termin_id" class="form-select select2">
                            <option value="">-- Semua Termin --</option>
                            @foreach ($termins as $termin)
                                <option value="{{ $termin->id }}" @selected($selectedTermin == $termin->id)>
                                    {{ $termin->nama_termin }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="kesesuaian_materi" class="form-label">Materi Tercapai</label>
                        <select id="kesesuaian_materi" name="kesesuaian_materi" class="form-select">
                            <option value="">-- Semua --</option>
                            <option value="sesuai" @selected($selectedKesesuaian === 'sesuai')>Sesuai</option>
                            <option value="sebagian" @selected($selectedKesesuaian === 'sebagian')>Sebagian</option>
                            <option value="tidak_sesuai" @selected($selectedKesesuaian === 'tidak_sesuai')>Tidak Sesuai</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 border-top">
                    <div class="d-flex align-items-center gap-2">
                        <label for="status" class="form-label mb-0 me-1">Status Verifikasi:</label>
                        <select id="status" name="status" class="form-select form-select-sm" style="width: auto;">
                            <option value="">-- Semua Status --</option>
                            <option value="draft" @selected($selectedStatus === 'draft')>Draft</option>
                            <option value="diajukan" @selected($selectedStatus === 'diajukan')>Diajukan</option>
                            <option value="diverifikasi" @selected($selectedStatus === 'diverifikasi')>Diverifikasi</option>
                            <option value="ditolak" @selected($selectedStatus === 'ditolak')>Ditolak</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bx bx-filter-alt me-1"></i> Terapkan Filter
                        </button>
                        @if ($selectedSemester || $selectedTermin || $selectedKesesuaian || $selectedStatus)
                            <a href="{{ route('ringkasan-perkuliahan.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bx bx-reset me-1"></i> Reset Filter
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <h5 class="card-header">Data Ringkasan Perkuliahan</h5>

        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Jadwal Monev</th>
                        <th>Perkuliahan</th>
                        <th>Jml Pertemuan</th>
                        <th>Kontrak Kuliah</th>
                        <th>Materi Tercapai</th>
                        <th>Pembuat</th>
                        <th>Keterangan (Temuan/Masalah)</th>
                        <th width="150">Aksi</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse ($ringkasanPerkuliahan as $item)
                        <tr>
                            <td>{{ $ringkasanPerkuliahan->firstItem() + $loop->index }}</td>
                            <td>
                                <strong>{{ $item->jadwalMonev->termin->nama_termin ?? '-' }}</strong>
                                <br>
                                <small>
                                    {{ $item->jadwalMonev->semester->tahunAkademik->nama ?? '-' }}
                                    -
                                    {{ ucfirst($item->jadwalMonev->semester->nama ?? '-') }}
                                </small>
                            </td>
                            <td style="max-width: 320px; white-space: normal;">
                                <strong>
                                    {{ $item->perkuliahan->mataKuliah->kode_mk ?? '-' }}
                                    -
                                    {{ $item->perkuliahan->mataKuliah->nama_mk ?? '-' }}
                                </strong>
                                <br>
                                <small>
                                    Kelas {{ $item->perkuliahan->kelas->nama_kelas ?? '-' }}
                                </small>
                            </td>
                            <td>{{ $item->jumlah_pertemuan }}</td>
                            <td>
                                <span class="badge {{ $item->kontrak_kuliah === 'ada' ? 'bg-label-success' : 'bg-label-danger' }}">
                                    {{ $item->kontrak_kuliah === 'ada' ? 'Ada' : 'Tidak Ada' }}
                                </span>
                            </td>
                            <td>
                                @if ($item->kesesuaian_materi === 'sesuai')
                                    <span class="badge bg-label-success">Sesuai</span>
                                @elseif ($item->kesesuaian_materi === 'sebagian')
                                    <span class="badge bg-label-warning">Sebagian</span>
                                @elseif ($item->kesesuaian_materi === 'tidak_sesuai')
                                    <span class="badge bg-label-danger">Tidak Sesuai</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $item->penginput->name ?? '-' }}</td>
                            <td style="max-width: 250px; white-space: normal;">
                                {{ $item->keterangan ?? '-' }}
                            </td>
                            <td>
                                <a href="{{ route('ringkasan-perkuliahan.show', $item) }}" class="btn btn-sm btn-icon btn-info" title="Lihat Detail">
                                    <i class="bx bx-show"></i>
                                </a>
                                @if ($item->canBeEditedBy(auth()->user()))
                                    <a href="{{ route('ringkasan-perkuliahan.edit', $item->id) }}"
                                        class="btn btn-sm btn-icon btn-warning" title="Edit Data">
                                        <i class="bx bx-edit"></i>
                                    </a>

                                    <form action="{{ route('ringkasan-perkuliahan.destroy', $item->id) }}" method="POST"
                                        class="d-inline" data-confirm-form
                                        data-confirm-title="Yakin ingin menghapus ringkasan ini?"
                                        data-confirm-text="Ringkasan yang dihapus tidak dapat dikembalikan."
                                        data-confirm-button-text="Ya, hapus" data-confirm-button-color="#ff3e1d">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-sm btn-icon btn-danger" title="Hapus Data">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">
                                Data ringkasan perkuliahan belum tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">@include('components._pagination', ['paginator' => $ringkasanPerkuliahan])</div>
@endsection
