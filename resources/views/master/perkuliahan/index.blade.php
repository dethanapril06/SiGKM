@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Perkuliahan</h4>

        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalSyncPerkuliahan">
                <i class="bx bx-sync me-1"></i> Sinkronkan SIAKAD
            </button>

            <a href="{{ route('perkuliahan.create') }}" class="btn btn-primary">
                <i class="bx bx-plus"></i> Tambah Perkuliahan
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible" role="alert">
            <i class="bx bx-x-circle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible" role="alert">
            <i class="bx bx-error-circle me-1"></i> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <h5 class="card-header">Data Perkuliahan</h5>

        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tahun Akademik</th>
                        <th>Semester</th>
                        <th>Mata Kuliah</th>
                        <th>Kelas</th>
                        <th>Dosen Pengajar</th>
                        <th>Status</th>
                        <th width="160">Aksi</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse ($perkuliahan as $item)
                        <tr>
                            <td>{{ $perkuliahan->firstItem() + $loop->index }}</td>
                            <td>
                                {{ $item->semester->tahunAkademik->nama ?? '-' }}
                            </td>
                            <td>
                                {{ ucfirst($item->semester->nama ?? '-') }}
                            </td>
                            <td>
                                <strong>
                                    {{ $item->mataKuliah->kode_mk ?? '-' }}
                                    -
                                    {{ $item->mataKuliah->nama_mk ?? '-' }}
                                </strong>
                            </td>
                            <td>{{ $item->kelas->nama_kelas ?? '-' }}</td>
                            <td>
                                {{ $item->pengajars->pluck('dosen.nama_dosen')->filter()->join(', ') ?: '-' }}
                            </td>
                            <td>
                                @if ($item->status === 'aktif')
                                    <span class="badge bg-label-success">Aktif</span>
                                @else
                                    <span class="badge bg-label-secondary">Selesai</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('perkuliahan.edit', $item->id) }}" class="btn btn-sm btn-icon btn-warning">
                                    <i class="bx bx-edit"></i>
                                </a>

                                <form action="{{ route('perkuliahan.destroy', $item->id) }}" method="POST"
                                    class="d-inline" data-confirm-form data-confirm-title="Yakin ingin menghapus data ini?"
                                    data-confirm-text="Data perkuliahan yang dihapus tidak dapat dikembalikan."
                                    data-confirm-button-text="Ya, hapus" data-confirm-button-color="#ff3e1d">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-sm btn-icon btn-danger">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">
                                Data perkuliahan belum tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
<div class="mt-3">@include('components._pagination', ['paginator' => $perkuliahan])</div>

    <!-- Modal Sinkronisasi Perkuliahan SIAKAD -->
    <div class="modal fade" id="modalSyncPerkuliahan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('sync-siakad.perkuliahan') }}" method="POST" class="modal-content" onsubmit="this.querySelector('#btn-submit-sync-perkuliahan').disabled = true; this.querySelector('#btn-submit-sync-perkuliahan').innerHTML = '<i class=\'bx bx-loader-alt bx-spin me-1\'></i> Menyinkronkan...';">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-sync me-1 text-primary"></i> Sinkronkan Perkuliahan dari SIAKAD</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Sistem akan menarik daftar mata kuliah, kelas, dan dosen pengajar dari SIAKAD Cloud Undana untuk semester yang Anda pilih.
                    </p>
                    <div class="mb-3">
                        <label class="form-label">Pilih Semester Target <span class="text-danger">*</span></label>
                        <select name="semester_id" class="form-select" required>
                            @foreach ($semesters ?? [] as $sem)
                                <option value="{{ $sem->id }}" @selected($sem->is_active)>
                                    {{ $sem->tahunAkademik->nama ?? '-' }} - {{ ucfirst($sem->nama) }}
                                    @if ($sem->is_active) (Semester Aktif) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btn-submit-sync-perkuliahan" class="btn btn-primary">
                        <i class="bx bx-cloud-download me-1"></i> Mulai Sinkronisasi
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
