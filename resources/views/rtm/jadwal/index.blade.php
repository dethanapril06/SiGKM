@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Jadwal RTM</h4>
        @if (auth()->user()->hasAnyRole(['ketua-gkm', 'anggota-gkm']))
            <a href="{{ route('jadwal-rtm.create') }}" class="btn btn-primary"><i class="bx bx-plus"></i> Tambah Jadwal</a>
        @endif
    </div>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    <div class="card">
        <h5 class="card-header">Data Jadwal RTM</h5>
        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Semester</th>
                        <th>RTM</th>
                        <th>Waktu & Lokasi</th>
                        <th>Status</th>
                        <th>Notulen</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jadwalRtm as $item)
                        <tr>
                            <td>{{ $jadwalRtm->firstItem() + $loop->index }}</td>
                            <td>{{ $item->semester?->label ?? '-' }}</td>
                            <td><strong>{{ $item->judul }}</strong><br><small>{{ Str::limit($item->agenda, 70) }}</small>
                            </td>
                            <td>{{ $item->tanggal?->format('d-m-Y') }}<br><small>{{ $item->lokasi ?: '-' }}</small></td>
                            <td><span
                                    class="badge bg-label-{{ $item->status === 'selesai' ? 'success' : ($item->status === 'terjadwal' ? 'primary' : 'secondary') }}">{{ ucfirst($item->status) }}</span>
                            </td>
                            <td>{{ $item->notulenRtm ? ucfirst($item->notulenRtm->status) : 'Belum ada' }}</td>
                            <td>
                                <a href="{{ route('jadwal-rtm.show', $item) }}" class="btn btn-sm btn-icon btn-info" title="Detail">
                                    <i class="bx bx-show"></i>
                                </a>
                                @if (auth()->user()->hasAnyRole(['ketua-gkm', 'anggota-gkm']))
                                    <a href="{{ route('jadwal-rtm.edit', $item) }}" class="btn btn-sm btn-icon btn-warning" title="Edit">
                                        <i class="bx bx-edit"></i>
                                    </a>
                                    <form action="{{ route('jadwal-rtm.destroy', $item) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Hapus jadwal ini?')">@csrf @method('DELETE')<button
                                            class="btn btn-sm btn-icon btn-danger" title="Hapus"><i class="bx bx-trash"></i></button></form>
                                @endif
                            </td>
                        </tr>
                    @empty<tr>
                            <td colspan="7" class="text-center">Belum ada jadwal RTM.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">@include('components._pagination', ['paginator' => $jadwalRtm])</div>
@endsection
