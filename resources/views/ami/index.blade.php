@extends('layouts.app')
@section('content')
    @php(
    $canManage = auth()->user()->hasAnyRole(['ketua-gkm', 'anggota-gkm'])
)
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Audit Mutu Internal (AMI)</h4>
            <p class="text-muted mb-0">Rekapan AMI per tahun akademik.</p>
        </div>
        @if ($canManage)
            <a href="{{ route('ami.create') }}" class="btn btn-primary"> <i class="bx bx-plus"></i> Tambah AMI</a>
        @endif
    </div>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    @forelse($ami as $item)
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-1">AMI {{ $item->tahunAkademik?->nama }}</h5>
                    <small>{{ $item->tanggal_pelaksanaan?->format('d-m-Y') }} · Diinput oleh
                        {{ $item->penginput?->name ?? '-' }}</small>
                </div>
                <span
                    class="badge bg-label-{{ $item->status === 'selesai' ? 'success' : ($item->status === 'aktif' ? 'primary' : 'secondary') }}">{{ ucfirst($item->status) }}</span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h6>Temuan</h6>
                        <p style="white-space:pre-line">{{ $item->temuan }}</p>
                    </div>
                    <div class="col-md-4">
                        <h6>Rekomendasi</h6>
                        <p style="white-space:pre-line">{{ $item->rekomendasi }}</p>
                    </div>
                    <div class="col-md-4">
                        <h6>Tindak Lanjut</h6>
                        <p style="white-space:pre-line">{{ $item->tindak_lanjut ?: '-' }}</p><small>Target:
                            {{ $item->target_selesai ?: '-' }}</small>
                    </div>
                </div>
                <hr>
                <h6>Bukti AMI</h6>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @forelse($item->dokumenAmis as $dokumen)
                        <div class="border rounded p-2">
                            <strong>{{ $dokumen->nama_dokumen }}</strong>
                            @if ($dokumen->file_path)
                                <a href="{{ asset('storage/' . $dokumen->file_path) }}" target="_blank"
                                    class="btn btn-sm btn-outline-primary ms-2">File</a>
                            @endif
                            @if ($dokumen->link_url)
                                <a href="{{ $dokumen->link_url }}" target="_blank" rel="noopener noreferrer"
                                    class="btn btn-sm btn-outline-info ms-1">Google Drive</a>
                            @endif
                            @if ($canManage)
                                <form action="{{ route('ami.dokumen.destroy', $dokumen) }}" method="POST"
                                    class="d-inline">@csrf @method('DELETE')<button
                                        class="btn btn-sm btn-outline-danger ms-1"
                                        onclick="return confirm('Hapus bukti ini?')"><i class="bx bx-trash"></i> Hapus</button></form>
                            @endif
                        </div>
                    @empty<span class="text-muted">Belum ada bukti.</span>
                    @endforelse
                </div>
                <a href="{{ route('ami.show', $item) }}" class="btn btn-info mb-3"><i class="bx bx-show"></i> Detail</a>
                @if ($canManage)
                    <form action="{{ route('ami.dokumen.store', $item) }}" method="POST" enctype="multipart/form-data"
                        class="border rounded p-3 mb-3">@csrf
                        <h6>Tambah Bukti</h6>
                        <div class="row">
                            <div class="col-md-4 mb-2"><input name="nama_dokumen" class="form-control"
                                    placeholder="Nama bukti" required></div>
                            <div class="col-md-4 mb-2"><input type="file" name="document_file"
                                    class="form-control"><small class="text-muted">Opsional, maksimal 5 MB.</small></div>
                            <div class="col-md-4 mb-2"><input type="url" name="link_url" class="form-control"
                                    placeholder="https://drive.google.com/..."><small class="text-muted">Boleh hanya mengisi
                                    link.</small></div>
                        </div><button class="btn btn-sm btn-primary">Tambah Bukti</button>
                    </form>
                    <a href="{{ route('ami.edit', $item) }}" class="btn btn-warning"><i class="bx bx-edit"></i> Edit</a>
                    <form action="{{ route('ami.destroy', $item) }}" method="POST" class="d-inline">@csrf
                        @method('DELETE')<button class="btn btn-danger"
                            onclick="return confirm('Hapus data AMI ini?')"><i class="bx bx-trash"></i> Hapus</button></form>
                @endif
            </div>
        </div>
    @empty<div class="alert alert-info">Belum ada data AMI.</div>
    @endforelse
    <div class="card">
        <div class="card-body">@include('components._pagination', ['paginator' => $ami])</div>
    </div>
@endsection
