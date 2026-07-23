@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Edit Realisasi RTL</h4>

        <a href="{{ route('rtl.index') }}" class="btn btn-secondary">
            <i class="bx bx-arrow-back"></i> Kembali
        </a>
    </div>

    <div class="card">
        <h5 class="card-header">Form Edit Realisasi Rencana Tindak Lanjut</h5>

        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('rtl.update', $rtl->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Temuan Evaluasi <span class="text-danger">*</span></label>
                    <select name="temuan_id" class="form-select @error('temuan_id') is-invalid @enderror">
                        <option value="">-- Pilih Temuan Evaluasi --</option>
                        @foreach ($temuan as $item)
                            <option value="{{ $item->id }}"
                                {{ old('temuan_id', $rtl->temuan_id) == $item->id ? 'selected' : '' }}>
                                {{ $item->kode_temuan }}
                                |
                                {{ $item->evaluasiIndikator->semester->tahunAkademik->nama ?? '-' }}
                                -
                                {{ $item->evaluasiIndikator->semester->nama ?? '-' }}
                                |
                                Temuan: {{ \Illuminate\Support\Str::limit($item->pernyataan, 70) }}
                                |
                                Rencana Awal: {{ \Illuminate\Support\Str::limit($item->rencana_awal ?? '-', 60) }}
                            </option>
                        @endforeach
                    </select>

                    @error('temuan_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Uraian Realisasi Tindak Lanjut <span class="text-danger">*</span></label>
                    <textarea name="uraian_realisasi" rows="5"
                        class="form-control @error('uraian_realisasi') is-invalid @enderror">{{ old('uraian_realisasi', $rtl->uraian_realisasi) }}</textarea>

                    @error('uraian_realisasi')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Waktu Pelaksanaan <span class="text-danger">*</span></label>
                    <input type="date" name="waktu_pelaksanaan"
                        class="form-control @error('waktu_pelaksanaan') is-invalid @enderror"
                        value="{{ old('waktu_pelaksanaan', $rtl->waktu_pelaksanaan?->format('Y-m-d')) }}">

                    @error('waktu_pelaksanaan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Catatan Tambahan (Opsional)</label>
                    <textarea name="catatan" rows="3"
                        class="form-control @error('catatan') is-invalid @enderror">{{ old('catatan', $rtl->catatan) }}</textarea>

                    @error('catatan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                @if ($rtl->buktiTindakLanjuts->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label">Bukti Saat Ini</label>
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach ($rtl->buktiTindakLanjuts as $bukti)
                                <a href="{{ asset('storage/' . $bukti->file_path) }}" target="_blank"
                                    class="btn btn-sm btn-outline-primary">
                                    <i class="bx bx-file"></i> Bukti {{ $loop->iteration }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label">Tambah Bukti Tindak Lanjut Baru (Opsional)</label>
                    <input type="file" name="bukti[]" multiple
                        class="form-control @error('bukti') is-invalid @enderror @error('bukti.*') is-invalid @enderror">
                    @error('bukti')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @error('bukti.*')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Kosongkan jika tidak ingin menambah bukti baru.</small>
                </div>

                <div class="mb-4">
                    <label class="form-label">Keterangan Bukti Baru</label>
                    <textarea name="keterangan_bukti[]" rows="2" class="form-control"
                        placeholder="Keterangan umum untuk bukti baru">{{ old('keterangan_bukti.0') }}</textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
