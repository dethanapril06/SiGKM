@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Edit Keanggotaan GKM</h4>

        <a href="{{ route('gkm-membership.index') }}" class="btn btn-secondary">
            <i class="bx bx-arrow-back"></i> Kembali
        </a>
    </div>

    <div class="card">
        <h5 class="card-header">Form Edit Keanggotaan GKM</h5>

        <div class="card-body">
            <form action="{{ route('gkm-membership.update', $gkmMembership->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Nama Anggota GKM <span class="text-danger">*</span></label>
                    <input type="text" name="nama_anggota"
                        class="form-control @error('nama_anggota') is-invalid @enderror"
                        value="{{ old('nama_anggota', $gkmMembership->nama_anggota) }}" placeholder="Masukkan nama lengkap anggota GKM">

                    @error('nama_anggota')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">NIP / NIDN (Opsional)</label>
                    <input type="text" name="nip"
                        class="form-control @error('nip') is-invalid @enderror"
                        value="{{ old('nip', $gkmMembership->nip) }}" placeholder="Masukkan NIP atau NIDN jika ada">

                    @error('nip')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Peran</label>
                    <select name="peran" class="form-select @error('peran') is-invalid @enderror">
                        <option value="">-- Pilih Peran --</option>
                        <option value="ketua" {{ old('peran', $gkmMembership->peran) == 'ketua' ? 'selected' : '' }}>
                            Ketua GKM
                        </option>
                        <option value="anggota" {{ old('peran', $gkmMembership->peran) == 'anggota' ? 'selected' : '' }}>
                            Anggota GKM
                        </option>
                    </select>

                    @error('peran')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai"
                        class="form-control @error('tanggal_mulai') is-invalid @enderror"
                        value="{{ old('tanggal_mulai', $gkmMembership->tanggal_mulai?->format('Y-m-d')) }}">

                    @error('tanggal_mulai')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai"
                        class="form-control @error('tanggal_selesai') is-invalid @enderror"
                        value="{{ old('tanggal_selesai', $gkmMembership->tanggal_selesai?->format('Y-m-d')) }}">

                    @error('tanggal_selesai')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                        {{ old('is_active', $gkmMembership->is_active) ? 'checked' : '' }}>

                    <label class="form-check-label" for="is_active">
                        Aktif
                    </label>
                </div>

                <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                    <i class="bx bx-info-circle me-2"></i>
                    <div>
                        Perubahan pada form ini hanya mengubah data keanggotaan GKM. Untuk mengedit email atau password akun login, silakan kelola melalui menu <strong>Manajemen Akun</strong>.
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bx bx-save"></i> Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
@endsection
