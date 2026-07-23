@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Keanggotaan GKM</h4>

        <a href="{{ route('gkm-membership.create') }}" class="btn btn-primary">
            <i class="bx bx-plus"></i> Tambah Keanggotaan
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
        <h5 class="card-header">Data Keanggotaan GKM</h5>

        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Anggota</th>
                        <th>Peran</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th>Status</th>
                        <th width="160">Aksi</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse ($gkmMembership as $item)
                        <tr>
                            <td>{{ $gkmMembership->firstItem() + $loop->index }}</td>
                            <td>
                                <strong>{{ $item->nama_anggota }}</strong>
                                @if ($item->nip)
                                    <br><small class="text-muted">NIP: {{ $item->nip }}</small>
                                @endif
                            </td>
                            <td>
                                @if ($item->peran === 'ketua')
                                    <span class="badge bg-label-primary">Ketua GKM</span>
                                @else
                                    <span class="badge bg-label-info">Anggota GKM</span>
                                @endif
                            </td>
                            <td>{{ $item->tanggal_mulai?->format('d-m-Y') ?? '-' }}</td>
                            <td>{{ $item->tanggal_selesai?->format('d-m-Y') ?? '-' }}</td>
                            <td>
                                @if ($item->is_active)
                                    <span class="badge bg-label-success">Aktif</span>
                                @else
                                    <span class="badge bg-label-secondary">Tidak Aktif</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('gkm-membership.edit', $item->id) }}" class="btn btn-sm btn-icon btn-warning">
                                    <i class="bx bx-edit"></i>
                                </a>

                                <form action="{{ route('gkm-membership.destroy', $item->id) }}" method="POST"
                                    class="d-inline" data-confirm-form data-confirm-title="Yakin ingin menghapus data ini?"
                                    data-confirm-text="Data keanggotaan GKM yang dihapus tidak dapat dikembalikan."
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
                            <td colspan="7" class="text-center">
                                Data keanggotaan GKM belum tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
<div class="mt-3">@include('components._pagination', ['paginator' => $gkmMembership])</div>
@endsection
