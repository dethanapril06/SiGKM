@extends('layouts.app')
@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Notulen RTM</h4>
        @can('create', App\Models\NotulenRtm::class)
            <a href="{{ route('notulen-rtm.create') }}" class="btn btn-primary"><i class="bx bx-plus"></i> Tambah Notulen</a>
        @endcan
    </div>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    <div class="card">
        <h5 class="card-header">Data Notulen RTM</h5>
        <div class="table-responsive text-nowrap">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>RTM</th>
                        <th>Isi Notulen</th>
                        <th>Status</th>
                        <th>Penginput / Verifikator</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notulenRtm as $item)
                        <tr>
                            <td>{{ $notulenRtm->firstItem() + $loop->index }}</td>
                            <td><strong>{{ $item->jadwalRtm?->judul }}</strong><br><small>{{ $item->jadwalRtm?->semester?->label }}</small>
                            </td>
                            <td style="min-width:260px;white-space:normal">{{ Str::limit(strip_tags($item->isi_notulen), 130) }}
                                @if ($item->catatan_verifikasi)
                                    <div class="text-danger mt-1"><small>Catatan: {{ $item->catatan_verifikasi }}</small>
                                    </div>
                                @endif
                            </td>
                            <td><span
                                    class="badge bg-label-{{ $item->status === 'diverifikasi' ? 'success' : ($item->status === 'diajukan' ? 'warning' : ($item->status === 'ditolak' ? 'danger' : 'secondary')) }}">{{ ucfirst($item->status) }}</span>
                            </td>
                            <td>{{ $item->penginput?->name ?? '-' }}<br><small>Verifikator:
                                    {{ $item->verifikator?->name ?? '-' }}</small></td>
                            <td style="min-width:260px">
                                <a href="{{ route('notulen-rtm.download-pdf', $item) }}"
                                    class="btn btn-sm btn-icon btn-secondary" title="Download File Gabungan RTM"><i class="bx bx-download"></i></a>
                                <a href="{{ route('notulen-rtm.show', $item) }}"
                                    class="btn btn-sm btn-icon btn-info" title="Detail"><i class="bx bx-show"></i></a>
                                @can('update', $item)
                                    <a href="{{ route('notulen-rtm.edit', $item) }}"
                                        class="btn btn-sm btn-icon btn-warning" title="Edit"><i class="bx bx-edit"></i></a>
                                @endcan
                                @can('submit', $item)
                                    <form action="{{ route('notulen-rtm.ajukan', $item) }}" method="POST" class="d-inline">
                                        @csrf @method('PATCH')<button class="btn btn-sm btn-icon btn-primary" title="Ajukan"><i class="bx bx-send"></i></button>
                                    </form>
                                @endcan
                                @can('verify', $item)
                                    <form action="{{ route('notulen-rtm.verifikasi', $item) }}" method="POST"
                                        class="d-inline">@csrf @method('PATCH')<button
                                            class="btn btn-sm btn-icon btn-success" title="Verifikasi"><i class="bx bx-check"></i></button></form>
                                    <button class="btn btn-sm btn-icon btn-danger" data-bs-toggle="modal"
                                        data-bs-target="#tolak-{{ $item->id }}" title="Tolak"><i class="bx bx-x"></i></button>
                                @endcan
                                @can('delete', $item)
                                    <form action="{{ route('notulen-rtm.destroy', $item) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')<button class="btn btn-sm btn-icon btn-danger" title="Hapus"><i class="bx bx-trash"></i></button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                        @can('reject', $item)
                            <div class="modal fade" id="tolak-{{ $item->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form action="{{ route('notulen-rtm.tolak', $item) }}" method="POST"
                                        class="modal-content">@csrf @method('PATCH')<div class="modal-header">
                                            <h5 class="modal-title">Tolak Notulen</h5>
                                        </div>
                                        <div class="modal-body"><label class="form-label">Catatan Perbaikan</label>
                                            <textarea name="catatan_verifikasi" class="form-control" required></textarea>
                                        </div>
                                        <div class="modal-footer"><button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Batal</button><button
                                                class="btn btn-danger">Tolak</button></div>
                                    </form>
                                </div>
                            </div>
                        @endcan
                    @empty<tr>
                            <td colspan="6" class="text-center">Belum ada notulen RTM.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">@include('components._pagination', ['paginator' => $notulenRtm])</div>
@endsection
