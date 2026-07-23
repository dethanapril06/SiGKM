@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Manajemen Akun</h4>

        <a href="{{ route('akun.create') }}" class="btn btn-primary">
            <i class="bx bx-plus"></i> Tambah Akun
        </a>
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

    <div class="card">
        <h5 class="card-header">Data Akun</h5>

        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Akun</th>
                        <th>Email Login</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th width="220">Aksi</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse ($akun as $item)
                        <tr>
                            <td>{{ $akun->firstItem() + $loop->index }}</td>
                            <td><strong>{{ $item->name }}</strong></td>
                            <td>{{ $item->email }}</td>
                            <td>
                                <span class="badge bg-label-primary">
                                    {{ $item->role->name ?? '-' }}
                                </span>
                            </td>
                            <td>
                                @if ($item->is_active)
                                    <span class="badge bg-label-success">Aktif</span>
                                @else
                                    <span class="badge bg-label-secondary">Tidak Aktif</span>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('akun.toggle-status', $item->id) }}" method="POST" class="d-inline"
                                    data-confirm-form
                                    data-confirm-title="{{ $item->is_active ? 'Nonaktifkan akun ini?' : 'Aktifkan akun ini?' }}"
                                    data-confirm-text="{{ $item->is_active ? 'Akun ini tidak akan bisa digunakan untuk login.' : 'Akun ini akan bisa digunakan kembali untuk login.' }}"
                                    data-confirm-button-text="{{ $item->is_active ? 'Ya, nonaktifkan' : 'Ya, aktifkan' }}"
                                    data-confirm-button-color="{{ $item->is_active ? '#8592a3' : '#71dd37' }}">
                                    @csrf
                                    @method('PATCH')

                                    <button type="submit"
                                        class="btn btn-sm {{ $item->is_active ? 'btn-secondary' : 'btn-success' }}"
                                        {{ auth()->id() === $item->id ? 'disabled' : '' }}>
                                        @if ($item->is_active)
                                            <i class="bx bx-block"></i>
                                        @else
                                            <i class="bx bx-check"></i>
                                        @endif
                                    </button>
                                </form>

                                <a href="{{ route('akun.edit', $item->id) }}" class="btn btn-sm btn-icon btn-warning">
                                    <i class="bx bx-edit"></i>
                                </a>

                                <form action="{{ route('akun.destroy', $item->id) }}" method="POST" class="d-inline"
                                    data-confirm-form data-confirm-title="Yakin ingin menghapus akun ini?"
                                    data-confirm-text="Akun yang dihapus tidak dapat dikembalikan."
                                    data-confirm-button-text="Ya, hapus" data-confirm-button-color="#ff3e1d">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-sm btn-icon btn-danger"
                                        {{ auth()->id() === $item->id ? 'disabled' : '' }}>
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">
                                Data akun belum tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
<div class="mt-3">@include('components._pagination', ['paginator' => $akun])</div>
@endsection
