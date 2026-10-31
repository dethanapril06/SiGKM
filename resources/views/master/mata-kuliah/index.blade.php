@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Mata Kuliah</h4>

        <div class="d-flex gap-2">
            <form action="{{ route('sync-siakad.mata-kuliah') }}" method="POST" class="d-inline" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerHTML = '<i class=\'bx bx-loader-alt bx-spin me-1\'></i> Menyinkronkan...';">
                @csrf
                <button type="submit" class="btn btn-outline-primary" title="Sinkronisasi Mata Kuliah dari SIAKAD Cloud Undana">
                    <i class="bx bx-sync me-1"></i> Sinkronkan SIAKAD
                </button>
            </form>

            <a href="{{ route('mata-kuliah.create') }}" class="btn btn-primary">
                <i class="bx bx-plus"></i> Tambah Mata Kuliah
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
        <h5 class="card-header">Data Mata Kuliah</h5>

        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode MK</th>
                        <th>Nama Mata Kuliah</th>
                        <th>SKS</th>
                        <th>Status</th>
                        <th width="160">Aksi</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse ($mataKuliah as $item)
                        <tr>
                            <td>{{ $mataKuliah->firstItem() + $loop->index }}</td>
                            <td><strong>{{ $item->kode_mk }}</strong></td>
                            <td>{{ $item->nama_mk }}</td>
                            <td>{{ $item->sks }}</td>
                            <td>
                                @if ($item->is_active)
                                    <span class="badge bg-label-success">Aktif</span>
                                @else
                                    <span class="badge bg-label-secondary">Tidak Aktif</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('mata-kuliah.edit', $item->id) }}" class="btn btn-sm btn-icon btn-warning">
                                    <i class="bx bx-edit"></i>
                                </a>

                                <form action="{{ route('mata-kuliah.destroy', $item->id) }}" method="POST" class="d-inline"
                                    data-confirm-form data-confirm-title="Yakin ingin menghapus data ini?"
                                    data-confirm-text="Data mata kuliah yang dihapus tidak dapat dikembalikan."
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
                            <td colspan="6" class="text-center">
                                Data mata kuliah belum tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
<div class="mt-3">@include('components._pagination', ['paginator' => $mataKuliah])</div>
@endsection
