@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Edit Evaluasi Indikator</h4>

        <a href="{{ route('evaluasi-indikator.index') }}" class="btn btn-secondary">
            <i class="bx bx-arrow-back"></i> Kembali
        </a>
    </div>

    <div class="card">
        <h5 class="card-header">Form Edit Evaluasi Indikator</h5>

        <div class="card-body">
            <form action="{{ route('evaluasi-indikator.update', $evaluasiIndikator->id) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Semester</label>
                    <select name="semester_id" class="form-select @error('semester_id') is-invalid @enderror">
                        <option value="">-- Pilih Semester --</option>

                        @foreach ($semester as $item)
                            <option value="{{ $item->id }}"
                                {{ old('semester_id', $evaluasiIndikator->semester_id) == $item->id ? 'selected' : '' }}>
                                {{ $item->tahunAkademik->nama ?? '-' }}
                                - {{ ucfirst($item->nama ?? '-') }}
                                {{ $item->is_active ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>

                    @error('semester_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    @php($selectedSource = old('evaluatable_key', $evaluasiIndikator->evaluatable_type.':'.$evaluasiIndikator->evaluatable_id))
                    <label class="form-label">Sumber Indikator</label>
                    <select name="evaluatable_key" class="form-select select2 @error('evaluatable_key') is-invalid @enderror" data-placeholder="-- Pilih Sumber Indikator --">
                        <option value="">-- Pilih Sumber Indikator --</option>

                        <optgroup label="Fakultas — Indikator Mutu">
                        @foreach ($indikatorMutu as $item)
                            <option value="indikator_mutu:{{ $item->id }}" @selected($selectedSource === 'indikator_mutu:'.$item->id)>
                                {{ $item->kode_indikator ? $item->kode_indikator . ' - ' : '' }}
                                {{ $item->isi_indikator }}
                            </option>
                        @endforeach
                        </optgroup>
                        <optgroup label="Program Studi — IKKS">
                            @foreach ($ikks as $item)
                                <option value="ikks:{{ $item->id }}" @selected($selectedSource === 'ikks:'.$item->id)>
                                    {{ $item->kode_ikks ? $item->kode_ikks.' - ' : '' }}{{ $item->uraian_ikks }}
                                </option>
                            @endforeach
                        </optgroup>
                    </select>

                    @error('evaluatable_key')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Status Capaian</label>
                    <select name="status_capaian" class="form-select @error('status_capaian') is-invalid @enderror">
                        <option value="">-- Pilih Status Capaian --</option>
                        <option value="tercapai"
                            {{ old('status_capaian', $evaluasiIndikator->status_capaian) === 'tercapai' ? 'selected' : '' }}>
                            Tercapai
                        </option>
                        <option value="hampir_tercapai"
                            {{ old('status_capaian', $evaluasiIndikator->status_capaian) === 'hampir_tercapai' ? 'selected' : '' }}>
                            Hampir Tercapai
                        </option>
                        <option value="belum_tercapai"
                            {{ old('status_capaian', $evaluasiIndikator->status_capaian) === 'belum_tercapai' ? 'selected' : '' }}>
                            Belum Tercapai
                        </option>
                    </select>

                    @error('status_capaian')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Bukti Capaian</label>
                    <input type="file" name="bukti_capaian"
                        class="form-control @error('bukti_capaian') is-invalid @enderror">

                    @if ($evaluasiIndikator->bukti_capaian)
                        <small class="d-block mt-2">
                            File saat ini:
                            <a href="{{ asset('storage/' . $evaluasiIndikator->bukti_capaian) }}" target="_blank">
                                Lihat file
                            </a>
                        </small>
                    @endif

                    @error('bukti_capaian')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Catatan</label>
                    <textarea name="catatan" rows="4" class="form-control @error('catatan') is-invalid @enderror"
                        placeholder="Masukkan catatan evaluasi indikator">{{ old('catatan', $evaluasiIndikator->catatan) }}</textarea>

                    @error('catatan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
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
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: $(this).data('placeholder') || '-- Pilih --',
                allowClear: true
            });
        });
    </script>
@endpush
