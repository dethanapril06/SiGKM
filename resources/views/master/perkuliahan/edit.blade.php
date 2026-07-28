@extends('layouts.app')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Edit Perkuliahan</h4>

        <a href="{{ route('perkuliahan.index') }}" class="btn btn-secondary">
            <i class="bx bx-arrow-back"></i> Kembali
        </a>
    </div>

    <div class="card">
        <h5 class="card-header">Form Edit Perkuliahan</h5>

        <div class="card-body">
            <form action="{{ route('perkuliahan.update', $perkuliahan->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Semester</label>
                    <select name="semester_id" class="form-select select2 @error('semester_id') is-invalid @enderror">
                        <option value="">-- Pilih Semester --</option>

                        @foreach ($semester as $item)
                            <option value="{{ $item->id }}"
                                {{ old('semester_id', $perkuliahan->semester_id) == $item->id ? 'selected' : '' }}>
                                {{ $item->tahunAkademik->nama ?? '-' }}
                                -
                                {{ ucfirst($item->nama ?? '-') }}
                                {{ $item->is_active ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>

                    @error('semester_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Mata Kuliah</label>
                    <select name="mata_kuliah_id" class="form-select select2 @error('mata_kuliah_id') is-invalid @enderror">
                        <option value="">-- Pilih Mata Kuliah --</option>

                        @foreach ($mataKuliah as $item)
                            <option value="{{ $item->id }}"
                                {{ old('mata_kuliah_id', $perkuliahan->mata_kuliah_id) == $item->id ? 'selected' : '' }}>
                                {{ $item->kode_mk }} - {{ $item->nama_mk }} ({{ $item->sks }} SKS)
                            </option>
                        @endforeach
                    </select>

                    @error('mata_kuliah_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Kelas</label>
                    <select name="kelas_id" class="form-select select2 @error('kelas_id') is-invalid @enderror">
                        <option value="">-- Pilih Kelas --</option>

                        @foreach ($kelas as $item)
                            <option value="{{ $item->id }}"
                                {{ old('kelas_id', $perkuliahan->kelas_id) == $item->id ? 'selected' : '' }}>
                                {{ $item->nama_kelas }}
                            </option>
                        @endforeach
                    </select>

                    @error('kelas_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Dosen Pengajar (Team Teaching)</label>
                    <select name="dosen_ids[]" id="dosen_ids" class="form-select select2 @error('dosen_ids') is-invalid @enderror @error('dosen_ids.*') is-invalid @enderror" multiple="multiple">
                        @foreach ($dosen as $item)
                            @php
                                $isOldSelected = is_array(old('dosen_ids')) && in_array($item->id, old('dosen_ids'));
                                $isDbSelected = !is_array(old('dosen_ids')) && in_array($item->id, $selectedDosenIds);
                            @endphp
                            <option value="{{ $item->id }}" {{ ($isOldSelected || $isDbSelected) ? 'selected' : '' }}>
                                {{ $item->nama_dosen }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Dapat memilih lebih dari 1 dosen pengampu perkuliahan.</small>

                    @error('dosen_ids')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror

                    @error('dosen_ids.*')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                        <option value="aktif" {{ old('status', $perkuliahan->status) == 'aktif' ? 'selected' : '' }}>
                            Aktif
                        </option>
                        <option value="selesai" {{ old('status', $perkuliahan->status) == 'selesai' ? 'selected' : '' }}>
                            Selesai
                        </option>
                    </select>

                    @error('status')
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

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2-dosen').select2({
                theme: 'bootstrap-5',
                placeholder: '-- Pilih Dosen Pengajar --',
                allowClear: true,
                width: '100%'
            });
        });
    </script>
@endpush
