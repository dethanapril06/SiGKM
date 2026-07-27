@extends('layouts.app')

@php
    $editing = isset($item) && $item;
    $field = match ($jenis) {
        'sasaran' => 'sasaran',
        'iku' => 'iku',
        'ikk' => 'ikk',
        'ikks' => 'ikks',
    };
    $parentField = match ($jenis) {
        'iku' => 'sasaran_strategis_id',
        'ikk' => 'indikator_kinerja_utama_id',
        'ikks' => 'indikator_kinerja_kegiatan_id',
        default => null,
    };
    $selectedParentId = $parentField ? old($parentField, $item?->{$parentField} ?? request('parent_id')) : null;
    $defaultKode = $editing
        ? old('kode_' . $field, $item?->{'kode_' . $field})
        : old('kode_' . $field, $jenis === 'sasaran' ? ($autoKodeMap['default'] ?? '') : ($autoKodeMap[$selectedParentId] ?? ''));
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">{{ $editing ? 'Edit' : 'Tambah' }} {{ $label }}</h4>
        <a href="{{ route('kinerja-program-studi.index') }}" class="btn btn-secondary"><i class="bx bx-arrow-back"></i>
            Kembali</a>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="POST"
                action="{{ $editing ? route('kinerja-program-studi.update', ['jenis' => $jenis, 'id' => $item]) : route('kinerja-program-studi.store', $jenis) }}">
                @csrf @if ($editing)
                    @method('PUT')
                @endif

                @if ($parentField)
                    <div class="mb-3">
                        <label class="form-label">Induk
                            {{ $jenis === 'iku' ? 'Sasaran Strategis' : ($jenis === 'ikk' ? 'IKU' : 'IKK') }}</label>
                        <select name="{{ $parentField }}" id="parent_select" class="form-select select2 @error($parentField) is-invalid @enderror" data-placeholder="-- Pilih Induk --">
                            <option value="">-- Pilih Induk --</option>
                            @foreach ($parents as $parent)
                                @php
                                    $parentLabel = match ($jenis) {
                                        'iku' => ($parent->kode_sasaran ?: 'Sasaran') . ' — ' . $parent->uraian_sasaran,
                                        'ikk' => ($parent->kode_iku ?: 'IKU') . ' — ' . $parent->uraian_iku,
                                        'ikks' => ($parent->kode_ikk ?: 'IKK') . ' — ' . $parent->uraian_ikk,
                                    };
                                @endphp
                                <option value="{{ $parent->id }}" @selected($selectedParentId == $parent->id)>{{ $parentLabel }}
                                </option>
                            @endforeach
                        </select>
                        @error($parentField)
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label">Kode {{ strtoupper($field) }}</label>
                    <input name="kode_{{ $field }}" id="kode_field"
                        class="form-control @error('kode_' . $field) is-invalid @enderror"
                        value="{{ $defaultKode }}" maxlength="50" placeholder="Contoh: {{ strtoupper($field) }}-01">
                    <div class="form-text">Kode terisi otomatis secara berurutan, namun tetap dapat disesuaikan manual.</div>

                    @error('kode_' . $field)
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Uraian {{ $label }}</label>
                    <textarea name="uraian_{{ $field }}" rows="5"
                        class="form-control @error('uraian_' . $field) is-invalid @enderror">{{ old('uraian_' . $field, $item?->{'uraian_' . $field}) }}</textarea>
                    @error('uraian_' . $field)
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" name="is_active" value="1" id="is_active"
                        @checked(old('is_active', $item?->is_active ?? true))>
                    <label for="is_active" class="form-check-label">Aktif</label>
                </div>
                <button class="btn btn-primary"><i class="bx bx-save"></i> Simpan</button>
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
            const isEditing = @json($editing);
            const autoKodeMap = @json($autoKodeMap ?? []);

            if ($('#parent_select').length) {
                $('#parent_select').select2({
                    theme: 'bootstrap-5',
                    placeholder: $(this).data('placeholder') || '-- Pilih Induk --',
                    allowClear: true
                }).on('change', function() {
                    if (!isEditing) {
                        const selectedId = $(this).val();
                        if (selectedId && autoKodeMap[selectedId]) {
                            $('#kode_field').val(autoKodeMap[selectedId]);
                        }
                    }
                });
            }
        });
    </script>
@endpush
