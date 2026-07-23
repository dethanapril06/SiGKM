@extends('layouts.app')
@section('content')
    <div class="py-3 mb-4">
        <h4 class="fw-bold mb-1">Verifikasi Data</h4>
        <p class="text-muted mb-0">Pusat verifikasi data yang diajukan kepada Ketua GKM.</p>
    </div>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    <div class="row mb-4">
        @foreach ([['Ringkasan', $ringkasanPerkuliahan->total(), 'primary'], ['Notulen RTM', $notulenRtm->total(), 'info']] as [$label, $count, $color])
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body text-center"><span
                            class="badge bg-label-{{ $color }} mb-2">{{ $label }}</span>
                        <h3 class="mb-0">{{ $count }}</h3>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card mb-4">
        <h5 class="card-header">Ringkasan Perkuliahan Menunggu Verifikasi</h5>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Perkuliahan</th>
                        <th>Dosen</th>
                        <th>Ringkasan</th>
                        <th>Penginput</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ringkasanPerkuliahan as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $item->perkuliahan?->mataKuliah?->nama_mk ?? '-' }}</strong><br><small>{{ $item->perkuliahan?->kelas?->nama_kelas }}
                                    | {{ $item->jadwalMonev?->semester?->label }}</small></td>
                            <td>{{ $item->perkuliahan?->pengajars?->pluck('dosen.nama_dosen')->filter()->join(', ') ?: '-' }}
                            </td>
                            <td>{{ $item->jumlah_pertemuan }}
                                pertemuan<br><small>{{ str($item->kesesuaian_materi)->replace('_', ' ')->title() }}</small>
                            </td>
                            <td>{{ $item->penginput?->name ?? '-' }}</td>
                            <td style="min-width:260px"><a href="{{ route('ringkasan-perkuliahan.show', $item) }}"
                                    class="btn btn-sm btn-icon btn-info" title="Detail"><i class="bx bx-show"></i></a>
                                <form action="{{ route('ringkasan-perkuliahan.verify', $item) }}" method="POST"
                                    class="d-inline">@csrf @method('PATCH')<button
                                        class="btn btn-sm btn-icon btn-success" title="Verifikasi"><i class="bx bx-check"></i></button></form> <button
                                    class="btn btn-sm btn-icon btn-danger" data-bs-toggle="modal"
                                    data-bs-target="#tolak-ringkasan-{{ $item->id }}" title="Tolak"><i class="bx bx-x"></i></button>
                            </td>
                        </tr>
                        @include('verifikasi.partials.modal-catatan', [
                            'modalId' => 'tolak-ringkasan-' . $item->id,
                            'action' => route('ringkasan-perkuliahan.reject', $item),
                            'title' => 'Tolak Ringkasan Perkuliahan',
                            'description' => 'Tuliskan perbaikan yang harus dilakukan oleh Anggota GKM.',
                            'fieldName' => 'catatan_verifikasi',
                            'required' => true,
                            'buttonClass' => 'btn-danger',
                            'buttonIcon' => 'bx bx-x',
                            'buttonText' => 'Tolak',
                        ])
                    @empty<tr>
                            <td colspan="6" class="text-center">Tidak ada ringkasan yang menunggu verifikasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">@include('components._pagination', ['paginator' => $ringkasanPerkuliahan])</div>
    </div>

    <div class="card">
        <h5 class="card-header">Notulen RTM Menunggu Verifikasi</h5>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>RTM</th>
                        <th>Isi Notulen</th>
                        <th>Penginput</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notulenRtm as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $item->jadwalRtm?->judul }}</strong><br><small>{{ $item->jadwalRtm?->semester?->label }}</small>
                            </td>
                            <td style="min-width:280px;white-space:normal">{{ Str::limit($item->isi_notulen, 150) }}</td>
                            <td>{{ $item->penginput?->name ?? '-' }}</td>
                            <td style="min-width:260px"><a href="{{ route('notulen-rtm.show', $item) }}"
                                    class="btn btn-sm btn-icon btn-info" title="Detail"><i class="bx bx-show"></i></a>
                                <form action="{{ route('notulen-rtm.verifikasi', $item) }}" method="POST"
                                    class="d-inline">@csrf @method('PATCH')<button
                                        class="btn btn-sm btn-icon btn-success" title="Verifikasi"><i class="bx bx-check"></i></button></form> <button
                                    class="btn btn-sm btn-icon btn-danger" data-bs-toggle="modal"
                                    data-bs-target="#tolak-notulen-{{ $item->id }}" title="Tolak"><i class="bx bx-x"></i></button>
                            </td>
                        </tr>
                        @include('verifikasi.partials.modal-catatan', [
                            'modalId' => 'tolak-notulen-' . $item->id,
                            'action' => route('notulen-rtm.tolak', $item),
                            'title' => 'Tolak Notulen RTM',
                            'description' => 'Tuliskan perbaikan yang harus dilakukan oleh Anggota GKM.',
                            'fieldName' => 'catatan_verifikasi',
                            'required' => true,
                            'buttonClass' => 'btn-danger',
                            'buttonIcon' => 'bx bx-x',
                            'buttonText' => 'Tolak',
                        ])
                    @empty<tr>
                            <td colspan="5" class="text-center">Tidak ada notulen yang menunggu verifikasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">@include('components._pagination', ['paginator' => $notulenRtm])</div>
    </div>
@endsection
