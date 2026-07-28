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
        @foreach ([['Laporan Perkuliahan', $laporanPerkuliahan->total(), 'primary'], ['Notulen RTM', $notulenRtm->total(), 'info']] as [$label, $count, $color])
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
        <h5 class="card-header">Laporan Pelaksanaan Perkuliahan Menunggu Verifikasi</h5>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Judul Laporan</th>
                        <th>Semester / Termin</th>
                        <th>Pengaju</th>
                        <th>Tanggal Pengajuan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($laporanPerkuliahan as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><strong>{{ $item->judul }}</strong></td>
                            <td>{{ $item->semester?->label }}<br><small>{{ $item->jadwalMonev?->termin?->nama_termin ?? '-' }}</small></td>
                            <td>{{ $item->pembuat?->name ?? '-' }}</td>
                            <td>{{ $item->updated_at->translatedFormat('d M Y H:i') }}</td>
                            <td style="min-width:200px">
                                <a href="{{ route('laporan.perkuliahan', ['semester_id' => $item->semester_id, 'jadwal_monev_id' => $item->jadwal_monev_id]) }}"
                                    class="btn btn-sm btn-icon btn-info" title="Lihat Laporan"><i class="bx bx-show"></i></a>
                                <form action="{{ route('laporan.verifikasi', $item) }}" method="POST"
                                    class="d-inline"
                                    data-confirm-form
                                    data-confirm-title="Verifikasi Laporan Pelaksanaan Perkuliahan?"
                                    data-confirm-text="Laporan akan diverifikasi dan dikunci."
                                    data-confirm-button-text="Ya, Verifikasi"
                                    data-confirm-icon="success">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm btn-icon btn-success" title="Verifikasi Laporan"><i class="bx bx-check"></i></button>
                                </form> 
                                <button class="btn btn-sm btn-icon btn-danger" data-bs-toggle="modal"
                                    data-bs-target="#tolak-laporan-{{ $item->id }}" title="Tolak Laporan"><i class="bx bx-x"></i></button>
                            </td>
                        </tr>
                        @include('verifikasi.partials.modal-catatan', [
                            'modalId' => 'tolak-laporan-' . $item->id,
                            'action' => route('laporan.tolak', $item),
                            'title' => 'Tolak Laporan Pelaksanaan Perkuliahan',
                            'description' => 'Tuliskan catatan perbaikan untuk pengaju laporan.',
                            'fieldName' => 'catatan_verifikasi',
                            'required' => true,
                            'buttonClass' => 'btn-danger',
                            'buttonIcon' => 'bx bx-x',
                            'buttonText' => 'Tolak Laporan',
                        ])
                    @empty<tr>
                            <td colspan="6" class="text-center">Tidak ada laporan perkuliahan yang menunggu verifikasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">@include('components._pagination', ['paginator' => $laporanPerkuliahan])</div>
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
                            <td style="min-width:280px;white-space:normal">{{ Str::limit(strip_tags($item->isi_notulen), 150) }}</td>
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
