@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Tambah Temuan Evaluasi</h4>

        <a href="{{ route('temuan-evaluasi.index') }}" class="btn btn-secondary">
            <i class="bx bx-arrow-back"></i> Kembali
        </a>
    </div>

    <div class="card">
        <h5 class="card-header">Form Tambah Temuan Evaluasi</h5>

        <div class="card-body">
            @if ($tingkatRisiko->isEmpty())
                <div class="alert alert-warning">
                    Data tingkat risiko belum tersedia. Isi tabel tingkat risiko terlebih dahulu agar risiko temuan bisa dicatat.
                </div>
            @endif

            <form action="{{ route('temuan-evaluasi.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Evaluasi Indikator</label>
                    <select name="evaluasi_indikator_id"
                        class="form-select @error('evaluasi_indikator_id') is-invalid @enderror">
                        <option value="">-- Pilih Evaluasi Indikator --</option>
                        @foreach ($evaluasiIndikator as $item)
                            <option value="{{ $item->id }}"
                                {{ old('evaluasi_indikator_id') == $item->id ? 'selected' : '' }}>
                                {{ $item->semester->tahunAkademik->nama ?? '-' }}
                                -
                                {{ $item->semester->nama ?? '-' }}
                                |
                                {{ $item->sumber_kode }}
                                -
                                {{ $item->sumber_uraian }} ({{ $item->sumber_jenis }})
                                |
                                {{ ucwords(str_replace('_', ' ', $item->status_capaian)) }}
                            </option>
                        @endforeach
                    </select>

                    @error('evaluasi_indikator_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                    <small class="text-muted">
                        Hanya indikator dengan status hampir tercapai atau belum tercapai yang ditampilkan.
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Kode Temuan</label>
                    <input type="text" name="kode_temuan" class="form-control @error('kode_temuan') is-invalid @enderror"
                        value="{{ old('kode_temuan', $kodeTemuan) }}">

                    @error('kode_temuan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Penanggung Jawab</label>
                    <input type="text" name="nama_penanggung_jawab"
                        class="form-control @error('nama_penanggung_jawab') is-invalid @enderror"
                        value="{{ old('nama_penanggung_jawab') }}"
                        placeholder="Tuliskan nama penanggung jawab tindak lanjut">

                    @error('nama_penanggung_jawab')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Pernyataan Temuan</label>
                    <textarea name="pernyataan" rows="4" class="form-control @error('pernyataan') is-invalid @enderror"
                        placeholder="Tuliskan pernyataan temuan berdasarkan hasil evaluasi indikator">{{ old('pernyataan') }}</textarea>

                    @error('pernyataan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Rencana Awal</label>
                    <textarea name="rencana_awal" rows="3" class="form-control @error('rencana_awal') is-invalid @enderror"
                        placeholder="Tuliskan rencana awal tindak lanjut">{{ old('rencana_awal') }}</textarea>

                    @error('rencana_awal')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Target Selesai</label>
                    <input type="date" name="target_selesai"
                        class="form-control @error('target_selesai') is-invalid @enderror"
                        value="{{ old('target_selesai') }}">

                    @error('target_selesai')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <hr class="my-4">

                <div class="mb-3">
                    <label class="form-label">Tingkat Risiko</label>
                    <select name="tingkat_risiko_id"
                        class="form-select @error('tingkat_risiko_id') is-invalid @enderror">
                        <option value="">-- Pilih Tingkat Risiko --</option>
                        @foreach ($tingkatRisiko as $item)
                            <option value="{{ $item->id }}"
                                {{ old('tingkat_risiko_id') == $item->id ? 'selected' : '' }}>
                                {{ $item->nama_tingkat }}
                            </option>
                        @endforeach
                    </select>

                    @error('tingkat_risiko_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Deskripsi Risiko</label>
                    <textarea name="deskripsi_risiko" rows="3" class="form-control @error('deskripsi_risiko') is-invalid @enderror"
                        placeholder="Tuliskan risiko yang mungkin terjadi">{{ old('deskripsi_risiko') }}</textarea>

                    @error('deskripsi_risiko')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Dampak Risiko</label>
                    <textarea name="dampak_risiko" rows="3" class="form-control @error('dampak_risiko') is-invalid @enderror"
                        placeholder="Tuliskan dampak risiko jika tidak ditindaklanjuti">{{ old('dampak_risiko') }}</textarea>

                    @error('dampak_risiko')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" name="aksi" value="draft" class="btn btn-secondary">
                        <i class="bx bx-save"></i> Simpan Draft
                    </button>

                    <button type="submit" name="aksi" value="terbuka" class="btn btn-primary">
                        <i class="bx bx-send"></i> Tandai Terbuka
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
