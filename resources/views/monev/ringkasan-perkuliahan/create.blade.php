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
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label mb-0">Perkuliahan</label>
                        <button type="button" id="btn-fetch-siakad" class="btn btn-xs btn-outline-primary" style="font-size: 0.8rem;">
                            <i class="bx bx-sync me-1"></i> Tarik Realisasi SIAKAD
                        </button>
                    </div>
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

                    <div id="siakad-status-alert" class="alert alert-info py-2 px-3 small d-none align-items-center mt-2 mb-0" role="alert">
                        <span id="siakad-status-text">Memeriksa realisasi dari SIAKAD Cloud...</span>
                    </div>

                    @error('perkuliahan_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Preview Sesi SIAKAD -->
                <div id="siakad-session-preview" class="card border mb-3 d-none">
                    <div class="card-header py-2 bg-light d-flex justify-content-between align-items-center">
                        <span class="small fw-bold text-primary"><i class="bx bx-check-circle me-1"></i> Data Sesi Pertemuan SIAKAD Cloud</span>
                        <span class="badge bg-label-primary" id="siakad-badge-summary">0 Selesai</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                            <table class="table table-sm table-striped mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40">#</th>
                                        <th>Tanggal</th>
                                        <th>Dosen Pengisi</th>
                                        <th>Status</th>
                                        <th>Rencana Materi</th>
                                        <th>Realisasi Materi</th>
                                    </tr>
                                </thead>
                                <tbody id="siakad-session-rows"></tbody>
                            </table>
                        </div>
                    </div>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnFetch = document.getElementById('btn-fetch-siakad');
    const selectPerkuliahan = document.querySelector('select[name="perkuliahan_id"]');
    const inputJumlah = document.querySelector('input[name="jumlah_pertemuan"]');
    const selectKesesuaian = document.querySelector('select[name="kesesuaian_materi"]');
    const selectKontrak = document.querySelector('select[name="kontrak_kuliah"]');
    const textareaKet = document.querySelector('textarea[name="keterangan"]');
    const alertStatus = document.getElementById('siakad-status-alert');
    const alertText = document.getElementById('siakad-status-text');
    const previewCard = document.getElementById('siakad-session-preview');
    const sessionRows = document.getElementById('siakad-session-rows');
    const badgeSummary = document.getElementById('siakad-badge-summary');

    function doFetchSiakad() {
        const perkuliahanId = selectPerkuliahan.value;
        if (!perkuliahanId) {
            alert('Silakan pilih Perkuliahan terlebih dahulu.');
            return;
        }

        btnFetch.disabled = true;
        btnFetch.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Menghubungi SIAKAD...';
        alertStatus.classList.remove('d-none', 'alert-danger', 'alert-success', 'alert-warning');
        alertStatus.classList.add('alert-info');
        alertText.textContent = 'Menghubungkan ke API SIAKAD Cloud Undana...';

        fetch(`{{ url('/ringkasan-perkuliahan') }}/${perkuliahanId}/fetch-siakad`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(res => {
            btnFetch.disabled = false;
            btnFetch.innerHTML = '<i class="bx bx-sync me-1"></i> Tarik Realisasi SIAKAD';

            if (!res.success || !res.data || !res.data.found) {
                alertStatus.classList.remove('alert-info');
                alertStatus.classList.add('alert-warning');
                alertText.textContent = res.message || 'Tidak ditemukan catatan sesi perkuliahan di SIAKAD untuk kelas ini.';
                previewCard.classList.add('d-none');
                return;
            }

            const d = res.data;
            inputJumlah.value = d.jumlah_pertemuan;
            selectKesesuaian.value = d.kesesuaian_materi;
            selectKontrak.value = d.kontrak_kuliah;
            if (!textareaKet.value.trim()) {
                textareaKet.value = d.catatan;
            }

            // Tampilkan preview tabel sesi
            sessionRows.innerHTML = '';
            (d.detail_pertemuan || []).forEach(item => {
                const tr = document.createElement('tr');
                const isSelesai = (item.status || '').toLowerCase() === 'selesai';
                const badgeStatus = isSelesai
                    ? '<span class="badge bg-label-success">Selesai</span>'
                    : `<span class="badge bg-label-secondary">${item.status || '-'}</span>`;

                tr.innerHTML = `
                    <td><strong>${item.pertemuan}</strong></td>
                    <td>${item.tanggal || '-'}</td>
                    <td>${item.dosen || '-'}</td>
                    <td>${badgeStatus}</td>
                    <td><span title="${item.rencana}">${item.rencana ? item.rencana.substring(0, 40) + '...' : '-'}</span></td>
                    <td><span title="${item.realisasi}">${item.realisasi ? item.realisasi.substring(0, 40) + '...' : '-'}</span></td>
                `;
                sessionRows.appendChild(tr);
            });

            badgeSummary.textContent = `${d.jumlah_pertemuan} Selesai dari ${(d.detail_pertemuan || []).length} Sesi`;
            previewCard.classList.remove('d-none');

            alertStatus.classList.remove('alert-info', 'alert-warning');
            alertStatus.classList.add('alert-success');
            alertText.innerHTML = `<strong>Berhasil ditarik!</strong> ${d.catatan} (Materi: ${d.kesesuaian_materi}, Kontrak: ${d.kontrak_kuliah}). Form telah terisi otomatis.`;
        })
        .catch(err => {
            btnFetch.disabled = false;
            btnFetch.innerHTML = '<i class="bx bx-sync me-1"></i> Tarik Realisasi SIAKAD';
            alertStatus.classList.remove('alert-info');
            alertStatus.classList.add('alert-danger');
            alertText.textContent = 'Gagal mengambil data dari SIAKAD: ' + err.message;
        });
    }

    btnFetch.addEventListener('click', doFetchSiakad);

    // Auto-trigger on change
    if (window.$ && $(selectPerkuliahan).hasClass('select2')) {
        $(selectPerkuliahan).on('select2:select', function () {
            if (selectPerkuliahan.value) {
                doFetchSiakad();
            }
        });
    } else {
        selectPerkuliahan.addEventListener('change', function () {
            if (selectPerkuliahan.value) {
                doFetchSiakad();
            }
        });
    }
});
</script>
@endpush
