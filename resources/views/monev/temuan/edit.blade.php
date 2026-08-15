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
                @php
                    $isProdi = old('scope_type', ($targetTemuan->evaluasiIndikator?->evaluatable_type === 'ikks' ? 'prodi' : 'fakultas')) === 'prodi';
                @endphp
                <div class="mb-4">
                    <label class="form-label fw-bold">Lingkup Temuan</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="scope_type" id="scope_fakultas" value="fakultas" @checked(! $isProdi)>
                        <label class="btn btn-outline-primary" for="scope_fakultas">
                            <i class="bx bx-building me-1"></i> Fakultas (Standar Mutu & Indikator Mutu)
                        </label>

                        <input type="radio" class="btn-check" name="scope_type" id="scope_prodi" value="prodi" @checked($isProdi)>
                        <label class="btn btn-outline-primary" for="scope_prodi">
                            <i class="bx bx-git-branch me-1"></i> Program Studi (Sasaran &rarr; IKU &rarr; IKK &rarr; IKKS)
                        </label>
                    </div>
                </div>

                <div class="card bg-lighter border mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold text-primary mb-3">Pilihan Evaluasi Indikator</h6>

                        {{-- Section 1: Fakultas Selection --}}
                        {{-- Section 1: Fakultas Selection --}}
                        <div id="section_fakultas" class="{{ $isProdi ? 'd-none' : '' }}">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Pilih Evaluasi Indikator Mutu <span class="text-danger">*</span></label>
                                <select id="fakultas_evaluasi_id" class="form-select select2 @error('evaluasi_indikator_id') is-invalid @enderror" data-placeholder="-- Cari atau Pilih Evaluasi Indikator Mutu --">
                                    <option value="">-- Pilih Evaluasi Indikator (Hampir Tercapai / Belum Tercapai) --</option>
                                    @foreach ($evaluasiFakultas as $ev)
                                        @php
                                            $indikator = $ev->evaluatable;
                                            $standar = $indikator?->standarMutu;
                                            $sem = $ev->semester ? ($ev->semester->tahunAkademik?->nama . ' ' . $ev->semester->nama) : '-';
                                            $statusLabel = $ev->status_capaian === 'dalam_proses' ? 'Hampir Tercapai' : ($ev->status_capaian === 'belum_tercapai' ? 'Belum Tercapai' : str($ev->status_capaian)->replace('_', ' ')->title());
                                            $isSelected = (string) old('evaluasi_indikator_id', $targetTemuan->evaluasi_indikator_id) === (string) $ev->id;
                                        @endphp
                                        <option value="{{ $ev->id }}"
                                            @selected($isSelected)
                                            data-standar="[{{ $standar?->kode_standar }}] {{ $standar?->nama_standar }}"
                                            data-indikator="[{{ $indikator?->kode_indikator }}] {{ $indikator?->isi_indikator }}"
                                            data-semester="{{ $sem }}"
                                            data-status="{{ $statusLabel }}"
                                            data-status-badge="{{ $ev->status_capaian === 'dalam_proses' ? 'warning' : 'danger' }}">
                                            [{{ $standar?->kode_standar ?? '-' }} &bull; {{ $indikator?->kode_indikator ?? '-' }}] {{ $indikator?->isi_indikator }} ({{ $sem }} | {{ $statusLabel }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text text-muted">
                                    <i class="bx bx-info-circle me-1"></i> Menampilkan evaluasi indikator mutu dengan capaian <strong>Hampir Tercapai</strong> atau <strong>Belum Tercapai</strong>. Ketik kode standar, kode indikator, atau kata kunci untuk mencari.
                                </div>
                            </div>

                            <div id="fakultas_evaluasi_detail" class="card bg-light border p-3 d-none">
                                <h6 class="fw-bold mb-2 text-primary"><i class="bx bx-info-circle me-1"></i> Detail Indikator Terpilih</h6>
                                <div class="row g-2 small">
                                    <div class="col-md-6">
                                        <strong>Standar Mutu:</strong> <span id="detail_standar">-</span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Semester:</strong> <span id="detail_semester">-</span>
                                    </div>
                                    <div class="col-12">
                                        <strong>Indikator Mutu:</strong> <span id="detail_indikator">-</span>
                                    </div>
                                    <div class="col-12">
                                        <strong>Status Capaian:</strong> <span id="detail_status">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Section 2: Prodi Selection --}}
                        <div id="section_prodi" class="{{ $isProdi ? '' : 'd-none' }}">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Pilih Semester <span class="text-danger">*</span></label>
                                    <select name="prodi_semester_id" id="prodi_semester_id" class="form-select select2" data-placeholder="-- Pilih Semester --">
                                        @foreach ($semesters as $sem)
                                            <option value="{{ $sem->id }}" @selected(old('prodi_semester_id', $currentSemesterId ?? $activeSemester?->id) == $sem->id)>
                                                {{ $sem->tahunAkademik?->nama }} - {{ ucfirst($sem->nama) }} {{ $sem->is_active ? '(Aktif)' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">1. Pilih Sasaran Strategis <span class="text-danger">*</span></label>
                                    <select id="prodi_sasaran_id" class="form-select select2" data-placeholder="-- Pilih Sasaran Strategis --">
                                        <option value="">-- Pilih Sasaran Strategis --</option>
                                        @foreach ($sasaranStrategises as $ss)
                                            <option value="{{ $ss->id }}">
                                                [{{ $ss->kode_sasaran }}] {{ $ss->uraian_sasaran }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">2. Pilih IKU (Indikator Kinerja Utama)</label>
                                    <select id="prodi_iku_id" class="form-select select2" data-placeholder="-- Pilih IKU --" disabled>
                                        <option value="">-- Pilih IKU --</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">3. Pilih IKK (Indikator Kinerja Kegiatan)</label>
                                    <select id="prodi_ikk_id" class="form-select select2" data-placeholder="-- Pilih IKK --" disabled>
                                        <option value="">-- Pilih IKK --</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">4. Pilih IKKS (Satuan) <span class="text-danger">*</span></label>
                                    <select name="prodi_ikks_id" id="prodi_ikks_id" class="form-select select2 @error('prodi_ikks_id') is-invalid @enderror" data-placeholder="-- Pilih IKKS --" disabled>
                                        <option value="">-- Pilih IKKS --</option>
                                    </select>
                                    @error('prodi_ikks_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
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
                    <label class="form-label">Penanggung Jawab</label>
                    <input type="text" name="nama_penanggung_jawab"
                        class="form-control @error('nama_penanggung_jawab') is-invalid @enderror"
                        value="{{ old('nama_penanggung_jawab', $targetTemuan->nama_penanggung_jawab) }}"
                        placeholder="Contoh: Koordinator Program Studi / Dekan / Nama Dosen / Unit Terkait">
                    @error('nama_penanggung_jawab')
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
                        value="{{ old('target_selesai', $targetTemuan->target_selesai) }}">
                    @error('target_selesai')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3 {{ $isProdi ? 'd-none' : '' }}" id="target_capaian_wrapper">
                    <label class="form-label">Target Capaian</label>
                    <textarea name="target_capaian" id="target_capaian" rows="3" class="form-control @error('target_capaian') is-invalid @enderror">{{ old('target_capaian', $targetTemuan->target_capaian) }}</textarea>
                    @error('target_capaian')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <hr class="my-4">

                <h6 class="fw-bold mb-3">Analisis Risiko (Opsional)</h6>

                <div class="mb-3">
                    <label class="form-label">Tingkat Risiko</label>
                    <select name="tingkat_risiko_id" id="tingkat_risiko_id"
                        class="form-select select2 @error('tingkat_risiko_id') is-invalid @enderror" data-placeholder="-- Tanpa Risiko --">
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
                    <textarea name="deskripsi_risiko" rows="3"
                        class="form-control @error('deskripsi_risiko') is-invalid @enderror">{{ old('deskripsi_risiko', $risiko?->deskripsi_risiko) }}</textarea>
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

    @push('scripts')
    <script>
        const sasaranStrategisesData = @json($sasaranStrategises);
        const currentSasaranId = {{ $currentSasaran?->id ?? 'null' }};
        const currentIkuId = {{ $currentIku?->id ?? 'null' }};
        const currentIkkId = {{ $currentIkk?->id ?? 'null' }};
        const currentIkksId = {{ $currentIkks?->id ?? 'null' }};

        $(document).ready(function () {
            const $scopeFakultasRadio = $('#scope_fakultas');
            const $scopeProdiRadio = $('#scope_prodi');
            const $sectionFakultas = $('#section_fakultas');
            const $sectionProdi = $('#section_prodi');
            const $hiddenEvaluasiIdInput = $('#evaluasi_indikator_id');

            const $fakultasEvaluasiSelect = $('#fakultas_evaluasi_id');
            const $detailCard = $('#fakultas_evaluasi_detail');
            const $detailStandar = $('#detail_standar');
            const $detailSemester = $('#detail_semester');
            const $detailIndikator = $('#detail_indikator');
            const $detailStatus = $('#detail_status');

            const $prodiSemesterSelect = $('#prodi_semester_id');
            const $prodiSasaranSelect = $('#prodi_sasaran_id');
            const $prodiIkuSelect = $('#prodi_iku_id');
            const $prodiIkkSelect = $('#prodi_ikk_id');
            const $prodiIkksSelect = $('#prodi_ikks_id');

            function initSelect2() {
                $('.select2').each(function() {
                    const $el = $(this);
                    $el.select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        placeholder: $el.data('placeholder') || $el.find('option:first').text() || '-- Pilih --',
                        allowClear: true,
                    });
                });
            }

            initSelect2();

            function updateFakultasDetail() {
                const val = $fakultasEvaluasiSelect.val();
                const $selectedOpt = $fakultasEvaluasiSelect.find(':selected');

                if (val && $selectedOpt.length && val !== "") {
                    $detailStandar.text($selectedOpt.data('standar') || '-');
                    $detailSemester.text($selectedOpt.data('semester') || '-');
                    $detailIndikator.text($selectedOpt.data('indikator') || '-');
                    const isWarning = $selectedOpt.data('status-badge') === 'warning';
                    const badgeClass = isWarning ? 'bg-warning text-dark' : 'bg-danger';
                    $detailStatus.html(`<span class="badge ${badgeClass}">${$selectedOpt.data('status') || '-'}</span>`);
                    $detailCard.removeClass('d-none');
                } else {
                    $detailCard.addClass('d-none');
                }
            }

            $fakultasEvaluasiSelect.on('change', function () {
                if ($scopeFakultasRadio.is(':checked')) {
                    $hiddenEvaluasiIdInput.val(this.value);
                }
                updateFakultasDetail();
            });

            $('input[name="scope_type"]').on('change', function () {
                if ($scopeFakultasRadio.is(':checked')) {
                    $sectionFakultas.removeClass('d-none');
                    $sectionProdi.addClass('d-none');
                    $('#target_capaian_wrapper').removeClass('d-none');
                    $hiddenEvaluasiIdInput.val($fakultasEvaluasiSelect.val());
                    updateFakultasDetail();
                    $fakultasEvaluasiSelect.select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        placeholder: $fakultasEvaluasiSelect.data('placeholder') || '-- Pilih --',
                        allowClear: true,
                    });
                } else {
                    $sectionProdi.removeClass('d-none');
                    $sectionFakultas.addClass('d-none');
                    $('#target_capaian_wrapper').addClass('d-none');
                    $hiddenEvaluasiIdInput.val('');
                    $([$prodiSemesterSelect, $prodiSasaranSelect, $prodiIkuSelect, $prodiIkkSelect, $prodiIkksSelect]).each(function() {
                        $(this).select2({
                            theme: 'bootstrap-5',
                            width: '100%',
                            placeholder: $(this).data('placeholder') || '-- Pilih --',
                            allowClear: true,
                        });
                    });
                }
            });

            if ($scopeProdiRadio.is(':checked')) {
                $sectionProdi.removeClass('d-none');
                $sectionFakultas.addClass('d-none');
                $('#target_capaian_wrapper').addClass('d-none');
                $hiddenEvaluasiIdInput.val('');
            } else {
                $sectionFakultas.removeClass('d-none');
                $sectionProdi.addClass('d-none');
                $('#target_capaian_wrapper').removeClass('d-none');
                if ($fakultasEvaluasiSelect.val()) {
                    $hiddenEvaluasiIdInput.val($fakultasEvaluasiSelect.val());
                    updateFakultasDetail();
                }
            }

            $prodiSasaranSelect.on('change', function () {
                const sasaranId = parseInt(this.value);
                $prodiIkuSelect.empty().append('<option value="">-- Pilih IKU --</option>').prop('disabled', true);
                $prodiIkkSelect.empty().append('<option value="">-- Pilih IKK --</option>').prop('disabled', true);
                $prodiIkksSelect.empty().append('<option value="">-- Pilih IKKS --</option>').prop('disabled', true);

                if (sasaranId) {
                    const selectedSasaran = sasaranStrategisesData.find(ss => ss.id === sasaranId);
                    if (selectedSasaran && selectedSasaran.indikator_kinerja_utamas) {
                        selectedSasaran.indikator_kinerja_utamas.forEach(iku => {
                            $prodiIkuSelect.append(new Option(`[${iku.kode_iku}] ${iku.uraian_iku}`, iku.id));
                        });
                        $prodiIkuSelect.prop('disabled', false);
                    }
                }

                $prodiIkuSelect.trigger('change');
            });

            $prodiIkuSelect.on('change', function () {
                const ikuId = parseInt(this.value);
                const sasaranId = parseInt($prodiSasaranSelect.val());
                $prodiIkkSelect.empty().append('<option value="">-- Pilih IKK --</option>').prop('disabled', true);
                $prodiIkksSelect.empty().append('<option value="">-- Pilih IKKS --</option>').prop('disabled', true);

                if (ikuId && sasaranId) {
                    const selectedSasaran = sasaranStrategisesData.find(ss => ss.id === sasaranId);
                    if (selectedSasaran) {
                        const selectedIku = selectedSasaran.indikator_kinerja_utamas.find(i => i.id === ikuId);
                        if (selectedIku && selectedIku.indikator_kinerja_kegiatans) {
                            selectedIku.indikator_kinerja_kegiatans.forEach(ikk => {
                                $prodiIkkSelect.append(new Option(`[${ikk.kode_ikk}] ${ikk.uraian_ikk}`, ikk.id));
                            });
                            $prodiIkkSelect.prop('disabled', false);
                        }
                    }
                }

                $prodiIkkSelect.trigger('change');
            });

            $prodiIkkSelect.on('change', function () {
                const ikkId = parseInt(this.value);
                const ikuId = parseInt($prodiIkuSelect.val());
                const sasaranId = parseInt($prodiSasaranSelect.val());
                $prodiIkksSelect.empty().append('<option value="">-- Pilih IKKS --</option>').prop('disabled', true);

                if (ikkId && ikuId && sasaranId) {
                    const selectedSasaran = sasaranStrategisesData.find(ss => ss.id === sasaranId);
                    if (selectedSasaran) {
                        const selectedIku = selectedSasaran.indikator_kinerja_utamas.find(i => i.id === ikuId);
                        if (selectedIku) {
                            const selectedIkk = selectedIku.indikator_kinerja_kegiatans.find(k => k.id === ikkId);
                            if (selectedIkk && selectedIkk.indikator_kinerja_kegiatan_satuan) {
                                const ikks = selectedIkk.indikator_kinerja_kegiatan_satuan;
                                $prodiIkksSelect.append(new Option(`[${ikks.kode_ikks}] ${ikks.uraian_ikks}`, ikks.id));
                                $prodiIkksSelect.prop('disabled', false);
                            }
                        }
                    }
                }

                $prodiIkksSelect.trigger('change');
            });

            // If editing a Prodi temuan, pre-populate cascade
            if ($scopeProdiRadio.is(':checked') && currentSasaranId) {
                $prodiSasaranSelect.val(currentSasaranId).trigger('change');
                if (currentIkuId) {
                    $prodiIkuSelect.val(currentIkuId).trigger('change');
                    if (currentIkkId) {
                        $prodiIkkSelect.val(currentIkkId).trigger('change');
                        if (currentIkksId) {
                            $prodiIkksSelect.val(currentIkksId).trigger('change');
                        }
                    }
                }
            }
        });
    </script>
    @endpush
@endsection
