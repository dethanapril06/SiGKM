@extends('layouts.app')
@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Keputusan RTM</h4><a href="{{ route('keputusan-rtm.create') }}" class="btn btn-primary"><i
                class="bx bx-plus"></i> Tambah
            Keputusan</a>
    </div>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <div class="card">
        <h5 class="card-header">Data Keputusan RTM</h5>
        <div class="table-responsive text-nowrap">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>RTM</th>
                        <th>Temuan yang Ditinjau</th>
                        <th>Keputusan</th>
                        <th>Strategi</th>
                        <th>Target</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($keputusanRtm as $item)
                        <tr>
                            <td>{{ $keputusanRtm->firstItem() + $loop->index }}</td>
                            <td><strong>{{ $item->notulenRtm?->jadwalRtm?->judul }}</strong><br><small>{{ $item->notulenRtm?->jadwalRtm?->semester?->label }}</small>
                            </td>
                            <td style="min-width:220px;white-space:normal">
                                <strong>{{ $item->temuan?->kode_temuan }}</strong><br>{{ Str::limit($item->temuan?->pernyataan, 100) }}<br><small>{{ $item->temuan?->nama_penanggung_jawab ?? '-' }}</small>
                            </td>
                            <td style="min-width:220px;white-space:normal">{{ Str::limit($item->uraian_keputusan, 110) }}
                            </td>
                            <td style="min-width:180px;white-space:normal">{{ Str::limit($item->strategi, 100) ?: '-' }}
                            </td>
                            <td>{{ $item->target_selesai ?: '-' }}</td>
                            <td><span
                                    class="badge bg-label-{{ $item->status === 'selesai' ? 'success' : ($item->status === 'proses' ? 'warning' : 'secondary') }}">{{ str($item->status)->replace('_', ' ')->title() }}</span>
                            </td>
                            <td><a href="{{ route('keputusan-rtm.show', $item) }}"
                                    class="btn btn-sm btn-icon btn-info" title="Detail"><i class="bx bx-show"></i></a> <a
                                    href="{{ route('keputusan-rtm.edit', $item) }}"
                                    class="btn btn-sm btn-icon btn-warning" title="Edit"><i class="bx bx-edit"></i></a>
                                <form action="{{ route('keputusan-rtm.destroy', $item) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')<button class="btn btn-sm btn-icon btn-danger" title="Hapus"><i class="bx bx-trash"></i></button>
                                </form>
                            </td>
                    </tr>@empty<tr>
                            <td colspan="8" class="text-center">Belum ada keputusan RTM.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">@include('components._pagination', ['paginator' => $keputusanRtm])</div>
@endsection
