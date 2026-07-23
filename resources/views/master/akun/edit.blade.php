@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Edit Akun</h4>

        <a href="{{ route('akun.index') }}" class="btn btn-secondary">
            <i class="bx bx-arrow-back"></i> Kembali
        </a>
    </div>

    <div class="card">
        <h5 class="card-header">Form Edit Akun</h5>

        <div class="card-body">
            <form action="{{ route('akun.update', $akun->id) }}" method="POST">
                @csrf
                @method('PUT')


                <div class="mb-3">
                    <label class="form-label">Role Akun</label>
                    <select name="role_id" class="form-select @error('role_id') is-invalid @enderror">
                        <option value="">-- Pilih Role --</option>

                        @foreach ($role as $item)
                            <option value="{{ $item->id }}"
                                {{ old('role_id', $akun->role_id) == $item->id ? 'selected' : '' }}>
                                {{ $item->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('role_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Nama Akun</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $akun->name) }}" placeholder="Contoh: Andi - Anggota GKM">

                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Email Login</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email', $akun->email) }}" placeholder="Contoh: andi@example.com">

                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <hr>

                <p class="text-muted">
                    Kosongkan password jika tidak ingin mengubah password.
                </p>

                <div class="mb-3">
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                        placeholder="Minimal 6 karakter">

                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation" class="form-control"
                        placeholder="Ulangi password baru">
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                        {{ old('is_active', $akun->is_active) ? 'checked' : '' }}>

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
