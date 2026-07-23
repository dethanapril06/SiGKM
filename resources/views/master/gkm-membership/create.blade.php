@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Tambah Keanggotaan GKM</h4>

        <a href="{{ route('gkm-membership.index') }}" class="btn btn-secondary">
            <i class="bx bx-arrow-back"></i> Kembali
        </a>
    </div>

    <div class="card">
        <h5 class="card-header">Form Tambah Keanggotaan GKM</h5>

        <div class="card-body">
            <form action="{{ route('gkm-membership.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Nama Anggota GKM <span class="text-danger">*</span></label>
                    <input type="text" name="nama_anggota"
                        class="form-control @error('nama_anggota') is-invalid @enderror"
                        value="{{ old('nama_anggota') }}" placeholder="Masukkan nama lengkap anggota GKM">

                    @error('nama_anggota')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">NIP / NIDN (Opsional)</label>
                    <input type="text" name="nip"
                        class="form-control @error('nip') is-invalid @enderror"
                        value="{{ old('nip') }}" placeholder="Masukkan NIP atau NIDN jika ada">

                    @error('nip')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Peran</label>
                    <select name="peran" class="form-select @error('peran') is-invalid @enderror">
                        <option value="">-- Pilih Peran --</option>
                        <option value="ketua" {{ old('peran') == 'ketua' ? 'selected' : '' }}>
                            Ketua GKM
                        </option>
                        <option value="anggota" {{ old('peran') == 'anggota' ? 'selected' : '' }}>
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
                        value="{{ old('tanggal_mulai') }}">

                    @error('tanggal_mulai')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai"
                        class="form-control @error('tanggal_selesai') is-invalid @enderror"
                        value="{{ old('tanggal_selesai') }}">

                    @error('tanggal_selesai')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                        {{ old('is_active', true) ? 'checked' : '' }}>

                    <label class="form-check-label" for="is_active">
                        Aktif
                    </label>
                </div>

                <hr class="my-4">

                <div class="card bg-light border mb-4">
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="create_account" value="1"
                                id="create_account" {{ old('create_account') ? 'checked' : '' }}
                                onchange="toggleAccountFields(this.checked)">

                            <label class="form-check-label fw-bold text-primary" for="create_account">
                                Sekaligus Buatkan Akun Login untuk Anggota/Ketua ini
                            </label>
                            <div class="form-text">
                                Akun login akan dibuat otomatis menggunakan Nama Anggota yang diinputkan dengan role sesuai Peran di atas.
                            </div>
                        </div>

                        <div id="account_fields" style="{{ old('create_account') ? '' : 'display: none;' }}">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Login <span class="text-danger">*</span></label>
                                    <input type="email" name="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        value="{{ old('email') }}" placeholder="contoh: dosen@prodi.ac.id">

                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password <span class="text-danger">*</span></label>
                                    <input type="password" name="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        placeholder="Minimal 6 karakter">

                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bx bx-save"></i> Simpan
                </button>
            </form>
        </div>
    </div>

    <script>
        function toggleAccountFields(isChecked) {
            const fields = document.getElementById('account_fields');
            if (fields) {
                fields.style.display = isChecked ? 'block' : 'none';
            }
        }
    </script>
@endsection
