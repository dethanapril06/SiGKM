@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Tambah Ringkasan Perkuliahan</h4>

        <a href="{{ route('ringkasan-perkuliahan.index') }}" class="btn btn-secondary">
            <i class="bx bx-arrow-back"></i> Kembali
        </a>
    </div>

    <div class="card">
        <h5 class="card-header">Form Tambah Ringkasan Perkuliahan</h5>

        <div class="card-body">
            <form action="{{ route('ringkasan-perkuliahan.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Jadwal Monev</label>
                    <select name="jadwal_monev_id" class="form-select select2 @error('jadwal_monev_id') is-invalid @enderror">
                        <option value="">-- Pilih Jadwal Monev --</option>

                        @foreach ($jadwalMonev as $item)
                            <option value="{{ $item->id }}" {{ old('jadwal_monev_id') == $item->id ? 'selected' : '' }}>
                                {{ $item->semester->tahunAkademik->nama ?? '-' }}
                                -
                                {{ ucfirst($item->semester->nama ?? '-') }}
                                |
                                {{ $item->termin->nama_termin ?? '-' }}
                            </option>
                        @endforeach
                    </select>

                    @error('jadwal_monev_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Perkuliahan</label>
                    <select name="perkuliahan_id" class="form-select select2 @error('perkuliahan_id') is-invalid @enderror">
                        <option value="">-- Pilih Perkuliahan --</option>

                        @foreach ($perkuliahan as $item)
                            <option value="{{ $item->id }}" {{ old('perkuliahan_id') == $item->id ? 'selected' : '' }}>
                                {{ $item->semester->tahunAkademik->nama ?? '-' }}
                                -
                                {{ ucfirst($item->semester->nama ?? '-') }}
                                |
                                {{ $item->mataKuliah->kode_mk ?? '-' }}
                                -
                                {{ $item->mataKuliah->nama_mk ?? '-' }}
                                |
                                Kelas {{ $item->kelas->nama_kelas ?? '-' }}
                            </option>
                        @endforeach
                    </select>

                    @error('perkuliahan_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Jumlah Pertemuan</label>
                    <input type="number" name="jumlah_pertemuan"
                        class="form-control @error('jumlah_pertemuan') is-invalid @enderror"
                        value="{{ old('jumlah_pertemuan') }}" placeholder="Contoh: 8">

                    @error('jumlah_pertemuan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Materi Tercapai</label>
                    <select name="kesesuaian_materi" class="form-select @error('kesesuaian_materi') is-invalid @enderror">
                        <option value="">-- Pilih Kesesuaian Materi --</option>
                        <option value="sesuai" {{ old('kesesuaian_materi') === 'sesuai' ? 'selected' : '' }}>
                            Sesuai
                        </option>
                        <option value="sebagian" {{ old('kesesuaian_materi') === 'sebagian' ? 'selected' : '' }}>
                            Sebagian
                        </option>
                        <option value="tidak_sesuai" {{ old('kesesuaian_materi') === 'tidak_sesuai' ? 'selected' : '' }}>
                            Tidak Sesuai
                        </option>
                    </select>

                    @error('kesesuaian_materi')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Kontrak Kuliah</label>
                    <select name="kontrak_kuliah" class="form-select @error('kontrak_kuliah') is-invalid @enderror">
                        <option value="ada" {{ old('kontrak_kuliah', 'ada') === 'ada' ? 'selected' : '' }}>
                            Ada
                        </option>
                        <option value="tidak_ada" {{ old('kontrak_kuliah') === 'tidak_ada' ? 'selected' : '' }}>
                            Tidak Ada
                        </option>
                    </select>

                    @error('kontrak_kuliah')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Keterangan (Temuan/Masalah)</label>
                    <textarea name="keterangan" rows="3" class="form-control @error('keterangan') is-invalid @enderror"
                        placeholder="Tuliskan keterangan jika ada">{{ old('keterangan') }}</textarea>

                    @error('keterangan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bx bx-save me-1"></i> Simpan Data Ringkasan
                </button>
            </form>
        </div>
    </div>
@endsection
