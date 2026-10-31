@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Tahun Akademik</h4>

        <div class="d-flex gap-2">
            <form action="{{ route('sync-siakad.periode') }}" method="POST" class="d-inline" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerHTML = '<i class=\'bx bx-loader-alt bx-spin me-1\'></i> Menyinkronkan...';">
                @csrf
                <button type="submit" class="btn btn-outline-primary" title="Sinkronisasi Periode & Semester dari SIAKAD Cloud Undana">
                    <i class="bx bx-sync me-1"></i> Sinkronkan SIAKAD
                </button>
            </form>

            <a href="{{ route('tahun-akademik.create') }}" class="btn btn-primary">
                <i class="bx bx-plus"></i> Tambah Tahun Akademik
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
        <h5 class="card-header">Data Tahun Akademik</h5>

        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tahun Akademik</th>
                        <th>Status Aktif</th>
                        <th width="180">Aksi</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($tahunAkademik as $item)
                        <tr>
                            <td>{{ $tahunAkademik->firstItem() + $loop->index }}</td>
                            <td>
                                <strong>{{ $item->nama }}</strong>
                            </td>
                            <td>
                                @if ($item->is_active)
                                    <span class="badge bg-label-success">Aktif</span>
                                @else
                                    <span class="badge bg-label-secondary">Tidak Aktif</span>
                                @endif
                            </td>
                            <td>
                                @if (!$item->is_active)
                                    <form action="{{ route('tahun-akademik.set-active', $item->id) }}" method="POST"
                                        class="d-inline" data-confirm-form
                                        data-confirm-title="Jadikan tahun akademik ini aktif?"
                                        data-confirm-text="Tahun akademik aktif sebelumnya akan digantikan."
                                        data-confirm-button-text="Ya, aktifkan" data-confirm-button-color="#71dd37">
                                        @csrf
                                        @method('PATCH')

                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="bx bx-check"></i>
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('tahun-akademik.edit', $item->id) }}" class="btn btn-sm btn-icon btn-warning">
                                    <i class="bx bx-edit"></i>
                                </a>

                                <form action="{{ route('tahun-akademik.destroy', $item->id) }}" method="POST"
                                    class="d-inline" data-confirm-form data-confirm-title="Yakin ingin menghapus data ini?"
                                    data-confirm-text="Data tahun akademik yang dihapus tidak dapat dikembalikan."
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
                            <td colspan="4" class="text-center">
                                Data tahun akademik belum tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
<div class="mt-3">@include('components._pagination', ['paginator' => $tahunAkademik])</div>
@endsection
