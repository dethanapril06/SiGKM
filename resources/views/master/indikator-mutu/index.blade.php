@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Indikator Mutu Fakultas</h4>

        <a href="{{ route('indikator-mutu.create', ['standar_mutu_id' => $standarMutuId]) }}" class="btn btn-primary">
            <i class="bx bx-plus"></i> Tambah Indikator
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

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('indikator-mutu.index') }}" method="GET" class="row g-3">
                <div class="col-md-10">
                    <label class="form-label">Filter Standar Mutu</label>
                    <select name="standar_mutu_id" id="standar_mutu_id" class="form-select select2" data-placeholder="-- Semua Standar Mutu --">
                        <option value="">-- Semua Standar Mutu --</option>

                        @foreach ($standarMutu as $item)
                            <option value="{{ $item->id }}"
                                {{ (string) $standarMutuId === (string) $item->id ? 'selected' : '' }}>
                                {{ $item->kode_standar ? $item->kode_standar . ' - ' : '' }}
                                {{ $item->nama_standar }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bx bx-filter"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <h5 class="card-header">Data Indikator Mutu</h5>

        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Standar Mutu</th>
                        <th>Kode</th>
                        <th>Isi Indikator</th>
                        <th width="160">Aksi</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse ($indikatorMutu as $item)
                        <tr>
                            <td>{{ $indikatorMutu->firstItem() + $loop->index }}</td>
                            <td>
                                <strong>{{ $item->standarMutu->nama_standar ?? '-' }}</strong>
                            </td>
                            <td>{{ $item->kode_indikator ?? '-' }}</td>
                            <td style="max-width: 400px; white-space: normal;">
                                {{ $item->isi_indikator }}
                            </td>
                            <td>
                                <a href="{{ route('indikator-mutu.edit', $item->id) }}"
                                    class="btn btn-sm btn-icon btn-warning">
                                    <i class="bx bx-edit"></i>
                                </a>

                                <form action="{{ route('indikator-mutu.destroy', $item->id) }}" method="POST"
                                    class="d-inline" data-confirm-form
                                    data-confirm-title="Yakin ingin menghapus indikator ini?"
                                    data-confirm-text="Data indikator mutu yang dihapus tidak dapat dikembalikan."
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
                            <td colspan="6" class="text-center">
                                Data indikator mutu belum tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">@include('components._pagination', ['paginator' => $indikatorMutu])</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#standar_mutu_id').select2({
                theme: 'bootstrap-5',
                placeholder: $(this).data('placeholder') || '-- Semua Standar Mutu --',
                allowClear: true
            });
        });
    </script>
@endpush
