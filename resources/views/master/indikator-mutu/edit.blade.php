@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Edit Indikator Mutu</h4>

        <a href="{{ route('indikator-mutu.index', ['standar_mutu_id' => $indikatorMutu->standar_mutu_id]) }}"
            class="btn btn-secondary">
            <i class="bx bx-arrow-back"></i> Kembali
        </a>
    </div>

    <div class="card">
        <h5 class="card-header">Form Edit Indikator Mutu</h5>

        <div class="card-body">
            <form action="{{ route('indikator-mutu.update', $indikatorMutu->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Standar Mutu</label>
                    <select name="standar_mutu_id" id="standar_mutu_id"
                        class="form-select select2 @error('standar_mutu_id') is-invalid @enderror"
                        data-placeholder="-- Pilih Standar Mutu --">
                        <option value="">-- Pilih Standar Mutu --</option>

                        @foreach ($standarMutu as $item)
                            <option value="{{ $item->id }}"
                                {{ old('standar_mutu_id', $indikatorMutu->standar_mutu_id) == $item->id ? 'selected' : '' }}>
                                {{ $item->kode_standar ? $item->kode_standar . ' - ' : '' }}
                                {{ $item->nama_standar }}
                            </option>
                        @endforeach
                    </select>

                    @error('standar_mutu_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Kode Indikator</label>
                    <input type="text" name="kode_indikator"
                        class="form-control @error('kode_indikator') is-invalid @enderror"
                        value="{{ old('kode_indikator', $indikatorMutu->kode_indikator) }}">

                    @error('kode_indikator')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Isi Indikator</label>
                    <textarea name="isi_indikator" rows="4" class="form-control @error('isi_indikator') is-invalid @enderror">{{ old('isi_indikator', $indikatorMutu->isi_indikator) }}</textarea>

                    @error('isi_indikator')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                        {{ old('is_active', $indikatorMutu->is_active) ? 'checked' : '' }}>

                    <label class="form-check-label" for="is_active">
                        Aktif
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bx bx-save"></i> Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
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
                placeholder: $(this).data('placeholder') || '-- Pilih Standar Mutu --',
                allowClear: true
            });
        });
    </script>
@endpush
