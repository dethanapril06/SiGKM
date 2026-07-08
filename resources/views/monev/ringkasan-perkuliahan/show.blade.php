@extends('layouts.app')
@section('content')
    <div class="d-flex justify-content-between py-3 mb-4">
        <h4 class="fw-bold">Detail Ringkasan Perkuliahan</h4><a href="{{ route('ringkasan-perkuliahan.index') }}"
            class="btn btn-secondary">Kembali</a>
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
        <div class="card-body">
            <x-detail-row label="Jadwal Monev">
                {{ $ringkasanPerkuliahan->jadwalMonev?->semester?->label }} —
                {{ $ringkasanPerkuliahan->jadwalMonev?->termin?->nama_termin }}
            </x-detail-row>
            <x-detail-row label="Mata Kuliah">
                {{ $ringkasanPerkuliahan->perkuliahan?->mataKuliah?->nama_mk ?? '-' }}
            </x-detail-row>
            <x-detail-row label="Kelas">
                {{ $ringkasanPerkuliahan->perkuliahan?->kelas?->nama_kelas ?? '-' }}
            </x-detail-row>
            <x-detail-row label="Dosen">
                {{ $ringkasanPerkuliahan->perkuliahan?->pengajars?->pluck('dosen.nama_dosen')->filter()->join(', ') ?: '-' }}
            </x-detail-row>
            <x-detail-row label="Jumlah Pertemuan">
                {{ $ringkasanPerkuliahan->jumlah_pertemuan }}
            </x-detail-row>
            <x-detail-row label="Kesesuaian Materi">
                {{ str($ringkasanPerkuliahan->kesesuaian_materi)->replace('_', ' ')->title() }}
            </x-detail-row>
            <x-detail-row label="Keterangan (Temuan/Masalah)"><span
                    style="white-space:pre-line">{{ $ringkasanPerkuliahan->keterangan ?: '-' }}</span></x-detail-row>
            <x-detail-row label="Status"><span
                    class="badge bg-label-{{ $ringkasanPerkuliahan->status === 'diverifikasi' ? 'success' : ($ringkasanPerkuliahan->status === 'ditolak' ? 'danger' : ($ringkasanPerkuliahan->status === 'diajukan' ? 'primary' : 'secondary')) }}">{{ ucfirst($ringkasanPerkuliahan->status) }}</span></x-detail-row>
            <x-detail-row label="Penginput">{{ $ringkasanPerkuliahan->penginput?->name }}</x-detail-row>
            <x-detail-row label="Verifikator">{{ $ringkasanPerkuliahan->verifikator?->name ?? '-' }} @if ($ringkasanPerkuliahan->verified_at)
                    ({{ $ringkasanPerkuliahan->verified_at->format('d-m-Y H:i') }})
                @endif
            </x-detail-row>
            <x-detail-row label="Catatan Verifikasi">{{ $ringkasanPerkuliahan->catatan_verifikasi ?: '-' }}</x-detail-row>
        </div>
    </div>

    @if ($ringkasanPerkuliahan->canBeEditedBy(auth()->user()))
        <div class="d-flex gap-2 mt-3">
            <a href="{{ route('ringkasan-perkuliahan.edit', $ringkasanPerkuliahan) }}" class="btn btn-warning">
                <i class="bx bx-edit"></i> Edit
            </a>

            @if ($ringkasanPerkuliahan->status !== 'diajukan')
                <form action="{{ route('ringkasan-perkuliahan.submit', $ringkasanPerkuliahan) }}" method="POST"
                    class="d-inline" data-confirm-form
                    data-confirm-title="Ajukan ringkasan ini ke Ketua GKM?"
                    data-confirm-text="Ringkasan akan masuk ke proses verifikasi."
                    data-confirm-button-text="Ya, ajukan" data-confirm-button-color="#696cff">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-send"></i> Ajukan
                    </button>
                </form>
            @endif

            <form action="{{ route('ringkasan-perkuliahan.destroy', $ringkasanPerkuliahan) }}" method="POST"
                class="d-inline" data-confirm-form
                data-confirm-title="Yakin ingin menghapus ringkasan ini?"
                data-confirm-text="Ringkasan yang dihapus tidak dapat dikembalikan."
                data-confirm-button-text="Ya, hapus" data-confirm-button-color="#ff3e1d">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="bx bx-trash"></i> Hapus
                </button>
            </form>
        </div>
    @endif
@endsection
