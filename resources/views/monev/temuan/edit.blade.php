@extends('layouts.app')

@section('content')
    @php
        $targetTemuan = $temuan ?? $temuanEvaluasi;
        $risiko = $targetTemuan->risikoTemuans->first();
    @endphp

    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Edit Temuan Evaluasi</h4>

        <a href="{{ route('temuan-evaluasi.fakultas') }}" class="btn btn-secondary">
            <i class="bx bx-arrow-back"></i> Kembali
        </a>
    </div>

    <div class="card">
        <h5 class="card-header">Form Edit Temuan Evaluasi</h5>

        <div class="card-body">
            <form action="{{ route('temuan-evaluasi.update', $targetTemuan->id) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="evaluasi_indikator_id" id="evaluasi_indikator_id" value="{{ old('evaluasi_indikator_id', $targetTemuan->evaluasi_indikator_id) }}">

                {{-- Scope Switcher: Fakultas vs Prodi --}}
                <div class="mb-4">
                    <label class="form-label fw-bold">Lingkup Temuan</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="scope_type" id="scope_fakultas" value="fakultas" checked>
                        <label class="btn btn-outline-primary" for="scope_fakultas">
                            <i class="bx bx-building me-1"></i> Fakultas (Standar Mutu & Indikator Mutu)
                        </label>

                        <input type="radio" class="btn-check" name="scope_type" id="scope_prodi" value="prodi">
                        <label class="btn btn-outline-primary" for="scope_prodi">
                            <i class="bx bx-git-branch me-1"></i> Program Studi (Sasaran $\rightarrow$ IKU $\rightarrow$ IKK $\rightarrow$ IKKS)
                        </label>
                    </div>
                </div>

                <div class="card bg-lighter border mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold text-primary mb-3">Pilihan Indikator Evaluasi (Bertingkat)</h6>

                        {{-- Section 1: Fakultas Selection --}}
                        <div id="section_fakultas">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">1. Pilih Standar Mutu</label>
                                    <select id="fakultas_standar_id" class="form-select">
                                        <option value="">-- Pilih Standar Mutu --</option>
                                        @foreach ($standarMutus as $sm)
                                            <option value="{{ $sm->id }}">
                                                [{{ $sm->kode_standar }}] {{ $sm->nama_standar }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">2. Pilih Indikator Mutu</label>
                                    <select id="fakultas_indikator_id" class="form-select" disabled>
                                        <option value="">-- Pilih Indikator Mutu --</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">3. Pilih Evaluasi Indikator <span class="text-danger">*</span></label>
                                    <select id="fakultas_evaluasi_id" class="form-select" disabled>
                                        <option value="">-- Pilih Evaluasi Indikator --</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Section 2: Prodi Selection --}}
                        <div id="section_prodi" class="d-none">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">1. Pilih Sasaran Strategis</label>
                                    <select id="prodi_sasaran_id" class="form-select">
                                        <option value="">-- Pilih Sasaran Strategis --</option>
                                        @foreach ($sasaranStrategises as $ss)
                                            <option value="{{ $ss->id }}">
                                                [{{ $ss->kode_sasaran }}] {{ \Illuminate\Support\Str::limit($ss->uraian_sasaran, 60) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">2. Pilih IKU (Indikator Kinerja Utama)</label>
                                    <select id="prodi_iku_id" class="form-select" disabled>
                                        <option value="">-- Pilih IKU --</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">3. Pilih IKK (Indikator Kinerja Kegiatan)</label>
                                    <select id="prodi_ikk_id" class="form-select" disabled>
                                        <option value="">-- Pilih IKK --</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">4. Pilih IKKS (Satuan)</label>
                                    <select id="prodi_ikks_id" class="form-select" disabled>
                                        <option value="">-- Pilih IKKS --</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">5. Pilih Evaluasi Indikator <span class="text-danger">*</span></label>
                                    <select id="prodi_evaluasi_id" class="form-select" disabled>
                                        <option value="">-- Pilih Evaluasi Indikator --</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        @error('evaluasi_indikator_id')
                            <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Kode Temuan <span class="text-danger">*</span></label>
                    <input type="text" name="kode_temuan" class="form-control @error('kode_temuan') is-invalid @enderror"
                        value="{{ old('kode_temuan', $targetTemuan->kode_temuan) }}">
                    @error('kode_temuan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Pernyataan Temuan <span class="text-danger">*</span></label>
                    <textarea name="pernyataan" rows="4" class="form-control @error('pernyataan') is-invalid @enderror">{{ old('pernyataan', $targetTemuan->pernyataan) }}</textarea>
                    @error('pernyataan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Rencana Tindak Lanjut</label>
                    <textarea name="rencana_awal" rows="3" class="form-control @error('rencana_awal') is-invalid @enderror">{{ old('rencana_awal', $targetTemuan->rencana_awal) }}</textarea>
                    @error('rencana_awal')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Target Selesai</label>
                    <input type="text" name="target_selesai"
                        class="form-control @error('target_selesai') is-invalid @enderror"
                        value="{{ old('target_selesai', $targetTemuan->target_selesai) }}"
                        placeholder="Contoh: Semester depan, Akhir Semester 2024/2025, dsb.">
                    @error('target_selesai')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Target Capaian</label>
                    <textarea name="target_capaian" rows="3" class="form-control @error('target_capaian') is-invalid @enderror"
                        placeholder="Tuliskan target capaian yang diharapkan">{{ old('target_capaian', $targetTemuan->target_capaian) }}</textarea>
                    @error('target_capaian')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <hr class="my-4">

                <h6 class="fw-bold mb-3">Analisis Risiko (Opsional)</h6>

                <div class="mb-3">
                    <label class="form-label">Tingkat Risiko</label>
                    <select name="tingkat_risiko_id"
                        class="form-select @error('tingkat_risiko_id') is-invalid @enderror">
                        <option value="">-- Tanpa Risiko --</option>
                        @foreach ($tingkatRisiko as $item)
                            <option value="{{ $item->id }}"
                                {{ old('tingkat_risiko_id', $risiko?->tingkat_risiko_id) == $item->id ? 'selected' : '' }}>
                                {{ $item->nama_tingkat }} (Nilai: {{ $item->nilai }})
                            </option>
                        @endforeach
                    </select>
                    @error('tingkat_risiko_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Deskripsi Risiko</label>
                    <textarea name="deskripsi_risiko" rows="3" class="form-control @error('deskripsi_risiko') is-invalid @enderror">{{ old('deskripsi_risiko', $risiko?->deskripsi_risiko) }}</textarea>
                    @error('deskripsi_risiko')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Dampak Risiko</label>
                    <textarea name="dampak_risiko" rows="3" class="form-control @error('dampak_risiko') is-invalid @enderror">{{ old('dampak_risiko', $risiko?->dampak_risiko) }}</textarea>
                    @error('dampak_risiko')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('temuan-evaluasi.fakultas') }}" class="btn btn-outline-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- JSON Data Serialization for Cascading Dropdowns --}}
    <script>
        const standarMutusData = @json($standarMutus);
        const sasaranStrategisesData = @json($sasaranStrategises);
        const currentEvaluasiId = parseInt(@json(old('evaluasi_indikator_id', $targetTemuan->evaluasi_indikator_id)));

        document.addEventListener('DOMContentLoaded', function () {
            const scopeFakultasRadio = document.getElementById('scope_fakultas');
            const scopeProdiRadio = document.getElementById('scope_prodi');
            const sectionFakultas = document.getElementById('section_fakultas');
            const sectionProdi = document.getElementById('section_prodi');
            const hiddenEvaluasiIdInput = document.getElementById('evaluasi_indikator_id');

            // Fakultas Elements
            const fakultasStandarSelect = document.getElementById('fakultas_standar_id');
            const fakultasIndikatorSelect = document.getElementById('fakultas_indikator_id');
            const fakultasEvaluasiSelect = document.getElementById('fakultas_evaluasi_id');

            // Prodi Elements
            const prodiSasaranSelect = document.getElementById('prodi_sasaran_id');
            const prodiIkuSelect = document.getElementById('prodi_iku_id');
            const prodiIkkSelect = document.getElementById('prodi_ikk_id');
            const prodiIkksSelect = document.getElementById('prodi_ikks_id');
            const prodiEvaluasiSelect = document.getElementById('prodi_evaluasi_id');

            // Scope Radio Switcher
            scopeFakultasRadio.addEventListener('change', function () {
                if (this.checked) {
                    sectionFakultas.classList.remove('d-none');
                    sectionProdi.classList.add('d-none');
                    hiddenEvaluasiIdInput.value = fakultasEvaluasiSelect.value;
                }
            });

            scopeProdiRadio.addEventListener('change', function () {
                if (this.checked) {
                    sectionProdi.classList.remove('d-none');
                    sectionFakultas.classList.add('d-none');
                    hiddenEvaluasiIdInput.value = prodiEvaluasiSelect.value;
                }
            });

            // 1. FAKULTAS CASCADE
            fakultasStandarSelect.addEventListener('change', function () {
                const standarId = parseInt(this.value);
                fakultasIndikatorSelect.innerHTML = '<option value="">-- Pilih Indikator Mutu --</option>';
                fakultasEvaluasiSelect.innerHTML = '<option value="">-- Pilih Evaluasi Indikator --</option>';
                fakultasIndikatorSelect.disabled = true;
                fakultasEvaluasiSelect.disabled = true;

                if (!standarId) return;

                const selectedStandar = standarMutusData.find(s => s.id === standarId);
                if (selectedStandar && selectedStandar.indikator_mutus) {
                    selectedStandar.indikator_mutus.forEach(im => {
                        const opt = document.createElement('option');
                        opt.value = im.id;
                        opt.textContent = `[${im.kode_indikator}] ${im.isi_indikator}`;
                        fakultasIndikatorSelect.appendChild(opt);
                    });
                    fakultasIndikatorSelect.disabled = false;
                }
            });

            fakultasIndikatorSelect.addEventListener('change', function () {
                const indikatorId = parseInt(this.value);
                const standarId = parseInt(fakultasStandarSelect.value);
                fakultasEvaluasiSelect.innerHTML = '<option value="">-- Pilih Evaluasi Indikator --</option>';
                fakultasEvaluasiSelect.disabled = true;

                if (!indikatorId) return;

                const selectedStandar = standarMutusData.find(s => s.id === standarId);
                if (selectedStandar) {
                    const selectedIndikator = selectedStandar.indikator_mutus.find(im => im.id === indikatorId);
                    if (selectedIndikator && selectedIndikator.evaluasi_indikators) {
                        selectedIndikator.evaluasi_indikators.forEach(ev => {
                            const opt = document.createElement('option');
                            opt.value = ev.id;
                            const sem = ev.semester ? `${ev.semester.tahun_akademik?.nama || ''} ${ev.semester.nama}` : '';
                            opt.textContent = `${sem} | Capaian: ${ev.status_capaian.replace('_', ' ')}`;
                            if (ev.id === currentEvaluasiId) opt.selected = true;
                            fakultasEvaluasiSelect.appendChild(opt);
                        });
                        fakultasEvaluasiSelect.disabled = false;
                        if (fakultasEvaluasiSelect.value) hiddenEvaluasiIdInput.value = fakultasEvaluasiSelect.value;
                    }
                }
            });

            fakultasEvaluasiSelect.addEventListener('change', function () {
                hiddenEvaluasiIdInput.value = this.value;
            });

            // 2. PRODI CASCADE
            prodiSasaranSelect.addEventListener('change', function () {
                const sasaranId = parseInt(this.value);
                prodiIkuSelect.innerHTML = '<option value="">-- Pilih IKU --</option>';
                prodiIkkSelect.innerHTML = '<option value="">-- Pilih IKK --</option>';
                prodiIkksSelect.innerHTML = '<option value="">-- Pilih IKKS --</option>';
                prodiEvaluasiSelect.innerHTML = '<option value="">-- Pilih Evaluasi Indikator --</option>';
                prodiIkuSelect.disabled = true;
                prodiIkkSelect.disabled = true;
                prodiIkksSelect.disabled = true;
                prodiEvaluasiSelect.disabled = true;

                if (!sasaranId) return;

                const selectedSasaran = sasaranStrategisesData.find(ss => ss.id === sasaranId);
                if (selectedSasaran && selectedSasaran.indikator_kinerja_utamas) {
                    selectedSasaran.indikator_kinerja_utamas.forEach(iku => {
                        const opt = document.createElement('option');
                        opt.value = iku.id;
                        opt.textContent = `[${iku.kode_iku}] ${iku.uraian_iku}`;
                        prodiIkuSelect.appendChild(opt);
                    });
                    prodiIkuSelect.disabled = false;
                }
            });

            prodiIkuSelect.addEventListener('change', function () {
                const ikuId = parseInt(this.value);
                const sasaranId = parseInt(prodiSasaranSelect.value);
                prodiIkkSelect.innerHTML = '<option value="">-- Pilih IKK --</option>';
                prodiIkksSelect.innerHTML = '<option value="">-- Pilih IKKS --</option>';
                prodiEvaluasiSelect.innerHTML = '<option value="">-- Pilih Evaluasi Indikator --</option>';
                prodiIkkSelect.disabled = true;
                prodiIkksSelect.disabled = true;
                prodiEvaluasiSelect.disabled = true;

                if (!ikuId) return;

                const selectedSasaran = sasaranStrategisesData.find(ss => ss.id === sasaranId);
                if (selectedSasaran) {
                    const selectedIku = selectedSasaran.indikator_kinerja_utamas.find(i => i.id === ikuId);
                    if (selectedIku && selectedIku.indikator_kinerja_kegiatans) {
                        selectedIku.indikator_kinerja_kegiatans.forEach(ikk => {
                            const opt = document.createElement('option');
                            opt.value = ikk.id;
                            opt.textContent = `[${ikk.kode_ikk}] ${ikk.uraian_ikk}`;
                            prodiIkkSelect.appendChild(opt);
                        });
                        prodiIkkSelect.disabled = false;
                    }
                }
            });

            prodiIkkSelect.addEventListener('change', function () {
                const ikkId = parseInt(this.value);
                const ikuId = parseInt(prodiIkuSelect.value);
                const sasaranId = parseInt(prodiSasaranSelect.value);
                prodiIkksSelect.innerHTML = '<option value="">-- Pilih IKKS --</option>';
                prodiEvaluasiSelect.innerHTML = '<option value="">-- Pilih Evaluasi Indikator --</option>';
                prodiIkksSelect.disabled = true;
                prodiEvaluasiSelect.disabled = true;

                if (!ikkId) return;

                const selectedSasaran = sasaranStrategisesData.find(ss => ss.id === sasaranId);
                if (selectedSasaran) {
                    const selectedIku = selectedSasaran.indikator_kinerja_utamas.find(i => i.id === ikuId);
                    if (selectedIku) {
                        const selectedIkk = selectedIku.indikator_kinerja_kegiatans.find(k => k.id === ikkId);
                        if (selectedIkk && selectedIkk.indikator_kinerja_kegiatan_satuan) {
                            const ikks = selectedIkk.indikator_kinerja_kegiatan_satuan;
                            const opt = document.createElement('option');
                            opt.value = ikks.id;
                            opt.textContent = `[${ikks.kode_ikks}] ${ikks.uraian_ikks}`;
                            prodiIkksSelect.appendChild(opt);
                            prodiIkksSelect.disabled = false;
                        }
                    }
                }
            });

            prodiIkksSelect.addEventListener('change', function () {
                const ikksId = parseInt(this.value);
                const ikkId = parseInt(prodiIkkSelect.value);
                const ikuId = parseInt(prodiIkuSelect.value);
                const sasaranId = parseInt(prodiSasaranSelect.value);
                prodiEvaluasiSelect.innerHTML = '<option value="">-- Pilih Evaluasi Indikator --</option>';
                prodiEvaluasiSelect.disabled = true;

                if (!ikksId) return;

                const selectedSasaran = sasaranStrategisesData.find(ss => ss.id === sasaranId);
                if (selectedSasaran) {
                    const selectedIku = selectedSasaran.indikator_kinerja_utamas.find(i => i.id === ikuId);
                    if (selectedIku) {
                        const selectedIkk = selectedIku.indikator_kinerja_kegiatans.find(k => k.id === ikkId);
                        if (selectedIkk && selectedIkk.indikator_kinerja_kegiatan_satuan) {
                            const ikks = selectedIkk.indikator_kinerja_kegiatan_satuan;
                            if (ikks.evaluasi_indikators) {
                                ikks.evaluasi_indikators.forEach(ev => {
                                    const opt = document.createElement('option');
                                    opt.value = ev.id;
                                    const sem = ev.semester ? `${ev.semester.tahun_akademik?.nama || ''} ${ev.semester.nama}` : '';
                                    opt.textContent = `${sem} | Capaian: ${ev.status_capaian.replace('_', ' ')}`;
                                    if (ev.id === currentEvaluasiId) opt.selected = true;
                                    prodiEvaluasiSelect.appendChild(opt);
                                });
                                prodiEvaluasiSelect.disabled = false;
                                if (prodiEvaluasiSelect.value) hiddenEvaluasiIdInput.value = prodiEvaluasiSelect.value;
                            }
                        }
                    }
                }
            });

            prodiEvaluasiSelect.addEventListener('change', function () {
                hiddenEvaluasiIdInput.value = this.value;
            });

            // Pre-select current evaluasi if editing
            if (currentEvaluasiId) {
                // Check if in Fakultas
                for (const sm of standarMutusData) {
                    if (sm.indikator_mutus) {
                        for (const im of sm.indikator_mutus) {
                            if (im.evaluasi_indikators && im.evaluasi_indikators.some(ev => ev.id === currentEvaluasiId)) {
                                scopeFakultasRadio.checked = true;
                                scopeFakultasRadio.dispatchEvent(new Event('change'));
                                fakultasStandarSelect.value = sm.id;
                                fakultasStandarSelect.dispatchEvent(new Event('change'));
                                fakultasIndikatorSelect.value = im.id;
                                fakultasIndikatorSelect.dispatchEvent(new Event('change'));
                                return;
                            }
                        }
                    }
                }

                // Check if in Prodi
                for (const ss of sasaranStrategisesData) {
                    if (ss.indikator_kinerja_utamas) {
                        for (const iku of ss.indikator_kinerja_utamas) {
                            if (iku.indikator_kinerja_kegiatans) {
                                for (const ikk of iku.indikator_kinerja_kegiatans) {
                                    const ikks = ikk.indikator_kinerja_kegiatan_satuan;
                                    if (ikks && ikks.evaluasi_indikators && ikks.evaluasi_indikators.some(ev => ev.id === currentEvaluasiId)) {
                                        scopeProdiRadio.checked = true;
                                        scopeProdiRadio.dispatchEvent(new Event('change'));
                                        prodiSasaranSelect.value = ss.id;
                                        prodiSasaranSelect.dispatchEvent(new Event('change'));
                                        prodiIkuSelect.value = iku.id;
                                        prodiIkuSelect.dispatchEvent(new Event('change'));
                                        prodiIkkSelect.value = ikk.id;
                                        prodiIkkSelect.dispatchEvent(new Event('change'));
                                        prodiIkksSelect.value = ikks.id;
                                        prodiIkksSelect.dispatchEvent(new Event('change'));
                                        return;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        });
    </script>
@endsection
