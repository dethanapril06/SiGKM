@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Ringkasan Perkuliahan</h4>

        @if (auth()->user()->hasAnyRole(['ketua-gkm', 'anggota-gkm']))
            <a href="{{ route('ringkasan-perkuliahan.create') }}" class="btn btn-primary">
                <i class="bx bx-plus"></i> Tambah Ringkasan
            </a>
        @endif
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
        <h5 class="card-header">Data Ringkasan Perkuliahan</h5>

        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Jadwal Monev</th>
                        <th>Perkuliahan</th>
                        <th>Jml Pertemuan</th>
                        <th>Materi Tercapai</th>
                        <th>Pembuat</th>
                        <th>Keterangan (Temuan/Masalah)</th>
                        <th>Status</th>
                        <th width="260">Aksi</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse ($ringkasanPerkuliahan as $item)
                        <tr>
                            <td>{{ $ringkasanPerkuliahan->firstItem() + $loop->index }}</td>
                            <td>
                                <strong>{{ $item->jadwalMonev->termin->nama_termin ?? '-' }}</strong>
                                <br>
                                <small>
                                    {{ $item->jadwalMonev->semester->tahunAkademik->nama ?? '-' }}
                                    -
                                    {{ ucfirst($item->jadwalMonev->semester->nama ?? '-') }}
                                </small>
                            </td>
                            <td style="max-width: 320px; white-space: normal;">
                                <strong>
                                    {{ $item->perkuliahan->mataKuliah->kode_mk ?? '-' }}
                                    -
                                    {{ $item->perkuliahan->mataKuliah->nama_mk ?? '-' }}
                                </strong>
                                <br>
                                <small>
                                    Kelas {{ $item->perkuliahan->kelas->nama_kelas ?? '-' }}
                                </small>
                            </td>
                            <td>{{ $item->jumlah_pertemuan }}</td>
                            <td>
                                @if ($item->kesesuaian_materi === 'sesuai')
                                    <span class="badge bg-label-success">Sesuai</span>
                                @elseif ($item->kesesuaian_materi === 'sebagian')
                                    <span class="badge bg-label-warning">Sebagian</span>
                                @elseif ($item->kesesuaian_materi === 'tidak_sesuai')
                                    <span class="badge bg-label-danger">Tidak Sesuai</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $item->penginput->name ?? '-' }}</td>
                            <td>
                                {{ $item->keterangan ?? '-' }}
                            </td>
                            <td>
                                <span
                                    class="badge bg-label-{{ $item->status === 'diajukan' ? 'primary' : ($item->status === 'diterima' ? 'success' : 'secondary') }}">
                                    {{ ucfirst($item->status ?? '-') }}
                                </span>
                            </td>
                            <td>
                                @if ($item->catatan_verifikasi)
                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal"
                                        data-bs-target="#noteModal{{ $item->id }}">
                                        <i class="bx bx-message-square-detail"></i>
                                    </button>

                                    <div class="modal fade" id="noteModal{{ $item->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Catatan Verifikasi</h5>
                                                    <button type="button" class="btn-close"
                                                        data-bs-dismiss="modal"></button>
                                                </div>

                                                <div class="modal-body">
                                                    <p class="mb-0 text-wrap">{{ $item->catatan_verifikasi }}</p>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">
                                                        Tutup
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <a href="{{ route('ringkasan-perkuliahan.show', $item) }}" class="btn btn-sm btn-icon btn-info"><i class="bx bx-show"></i></a>
                                @if ($item->canBeEditedBy(auth()->user()))
                                    <a href="{{ route('ringkasan-perkuliahan.edit', $item->id) }}"
                                        class="btn btn-sm btn-icon btn-warning">
                                        <i class="bx bx-edit"></i>
                                    </a>

                                    @if (auth()->user()->hasRole('anggota-gkm') && $item->status !== 'diajukan')
                                        <form action="{{ route('ringkasan-perkuliahan.submit', $item->id) }}"
                                            method="POST" class="d-inline" data-confirm-form
                                            data-confirm-title="Ajukan ringkasan ini ke Ketua GKM?"
                                            data-confirm-text="Ringkasan akan masuk ke proses verifikasi."
                                            data-confirm-button-text="Ya, ajukan" data-confirm-button-color="#696cff">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="bx bx-send"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <form action="{{ route('ringkasan-perkuliahan.destroy', $item->id) }}" method="POST"
                                        class="d-inline" data-confirm-form
                                        data-confirm-title="Yakin ingin menghapus ringkasan ini?"
                                        data-confirm-text="Ringkasan yang dihapus tidak dapat dikembalikan."
                                        data-confirm-button-text="Ya, hapus" data-confirm-button-color="#ff3e1d">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-sm btn-icon btn-danger">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </form>
                                @endif

                                @if (auth()->user()->hasRole('ketua-gkm') && $item->status === 'diajukan')
                                    <form action="{{ route('ringkasan-perkuliahan.verify', $item->id) }}" method="POST"
                                        class="d-inline" data-confirm-form
                                        data-confirm-title="Verifikasi ringkasan ini?"
                                        data-confirm-text="Ringkasan akan ditandai sebagai diverifikasi."
                                        data-confirm-button-text="Ya, verifikasi" data-confirm-button-color="#71dd37">
                                        @csrf
                                        @method('PATCH')

                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="bx bx-check"></i>
                                        </button>
                                    </form>

                                    <button type="button" class="btn btn-sm btn-icon btn-danger" data-bs-toggle="modal"
                                        data-bs-target="#rejectModal{{ $item->id }}">
                                        <i class="bx bx-x"></i>
                                    </button>

                                    <div class="modal fade" id="rejectModal{{ $item->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <form action="{{ route('ringkasan-perkuliahan.reject', $item->id) }}"
                                                method="POST" class="modal-content">
                                                @csrf
                                                @method('PATCH')

                                                <div class="modal-header">
                                                    <h5 class="modal-title">Tolak Ringkasan</h5>
                                                    <button type="button" class="btn-close"
                                                        data-bs-dismiss="modal"></button>
                                                </div>

                                                <div class="modal-body">
                                                    <label class="form-label">Catatan Penolakan</label>
                                                    <textarea name="catatan_verifikasi" rows="4" class="form-control" placeholder="Masukkan alasan penolakan"
                                                        required></textarea>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">
                                                        Batal
                                                    </button>
                                                    <button type="submit" class="btn btn-danger">
                                                        Tolak
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">
                                Data ringkasan perkuliahan belum tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
<div class="mt-3">@include('components._pagination', ['paginator' => $ringkasanPerkuliahan])</div>
@endsection
