@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Tambah Standar Mutu Fakultas</h4>

        <a href="{{ route('standar-mutu.index') }}" class="btn btn-secondary">
            <i class="bx bx-arrow-back"></i> Kembali
        </a>
    </div>

    <div class="card">
        <h5 class="card-header">Form Tambah Standar Mutu</h5>

        <div class="card-body">
            <form action="{{ route('standar-mutu.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Kode Standar</label>
                    <input type="text" name="kode_standar"
                        class="form-control @error('kode_standar') is-invalid @enderror" value="{{ old('kode_standar', $autoKode) }}"
                        placeholder="Contoh: STD-001">
                    <div class="form-text">Kode standar terisi otomatis secara berurutan, namun tetap dapat disesuaikan manual jika diperlukan.</div>

                    @error('kode_standar')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Nama Standar</label>
                    <input type="text" name="nama_standar"
                        class="form-control @error('nama_standar') is-invalid @enderror" value="{{ old('nama_standar') }}"
                        placeholder="Masukkan nama standar mutu">

                    @error('nama_standar')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="deskripsi" rows="4" class="form-control @error('deskripsi') is-invalid @enderror"
                        placeholder="Masukkan deskripsi standar mutu">{{ old('deskripsi') }}</textarea>

                    @error('deskripsi')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                        {{ old('is_active', true) ? 'checked' : '' }}>

                    <label class="form-check-label" for="is_active">
                        Aktif
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bx bx-save"></i> Simpan
                </button>
            </form>
        </div>
    </div>
@endsection
