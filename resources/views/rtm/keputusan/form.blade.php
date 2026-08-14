@php($current = $keputusanRtm ?? null)
<div class="alert alert-info">
    <i class="bx bx-info-circle me-1"></i> Pilih <strong>Semester</strong> terlebih dahulu, lalu pilih <strong>Temuan yang Ditinjau</strong>. Pilihan <strong>Notulen RTM</strong> bersifat opsional.
</div>

<div class="mb-3">
    <label class="form-label">Semester <span class="text-danger">*</span></label>
    <select id="semester_id" name="semester_id" class="form-select select2 @error('semester_id') is-invalid @enderror" required>
        <option value="">-- Pilih Semester --</option>
        @foreach ($semesters as $item)
            <option value="{{ $item->id }}" @selected(old('semester_id', $current?->semester_id ?? $current?->notulenRtm?->jadwalRtm?->semester_id ?? $current?->temuan?->evaluasiIndikator?->semester_id) == $item->id)>
                {{ $item->tahunAkademik?->nama ?? '-' }} - {{ ucfirst($item->nama) }}
            </option>
        @endforeach
    </select>
    @error('semester_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Temuan yang Ditinjau <span class="text-danger">*</span></label>
    <select id="temuan_id" name="temuan_id"
        class="form-select select2 @error('temuan_id') is-invalid @enderror" required
        data-selected="{{ old('temuan_id', $current?->temuan_id) }}">
        <option value="">-- Pilih Semester terlebih dahulu --</option>
    </select>
    @error('temuan_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Notulen RTM Terverifikasi <span class="text-muted fw-normal">(Opsional)</span></label>
    <select id="notulen_rtm_id" name="notulen_rtm_id"
        class="form-select select2 @error('notulen_rtm_id') is-invalid @enderror"
        data-selected="{{ old('notulen_rtm_id', $current?->notulen_rtm_id) }}">
        <option value="">-- Tanpa RTM (Opsional) --</option>
    </select>
    <small class="text-muted d-block mt-1">Kosongkan jika keputusan dibuat langsung per semester tanpa mengaitkan ke notulen RTM tertentu.</small>
    @error('notulen_rtm_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Uraian Keputusan <span class="text-danger">*</span></label>
    <textarea name="uraian_keputusan" rows="5" class="form-control @error('uraian_keputusan') is-invalid @enderror"
        placeholder="Masukkan rincian keputusan hasil evaluasi..." required>{{ old('uraian_keputusan', $current?->uraian_keputusan) }}</textarea>
    @error('uraian_keputusan')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Strategi <span class="text-muted fw-normal">(Opsional)</span></label>
    <textarea name="strategi" rows="4" class="form-control @error('strategi') is-invalid @enderror"
        placeholder="Strategi untuk menjalankan keputusan RTM...">{{ old('strategi', $current?->strategi) }}</textarea>
    @error('strategi')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const temuanBySemester = @json($temuanBySemester);
            const notulenBySemester = @json($notulenBySemester);
            const semesterEl = document.getElementById('semester_id');
            const temuanEl = document.getElementById('temuan_id');
            const notulenEl = document.getElementById('notulen_rtm_id');

            let selectedTemuan = String(temuanEl.dataset.selected || '');
            let selectedNotulen = String(notulenEl.dataset.selected || '');

            const refreshTemuan = () => {
                const semVal = semesterEl.value;
                const options = temuanBySemester[semVal] || [];
                temuanEl.innerHTML = '<option value="">-- Pilih Temuan --</option>';
                options.forEach(item => {
                    const opt = new Option(item.label, item.id, false, String(item.id) === selectedTemuan);
                    temuanEl.add(opt);
                });
                if (!options.length && semVal) {
                    temuanEl.innerHTML = '<option value="">Tidak ada Temuan yang tersedia untuk Semester ini</option>';
                } else if (!semVal) {
                    temuanEl.innerHTML = '<option value="">-- Pilih Semester terlebih dahulu --</option>';
                }
                if ($(temuanEl).data('select2')) {
                    $(temuanEl).trigger('change.select2');
                }
            };

            const refreshNotulen = () => {
                const semVal = semesterEl.value;
                const options = notulenBySemester[semVal] || [];
                notulenEl.innerHTML = '<option value="">-- Tanpa RTM (Opsional) --</option>';
                options.forEach(item => {
                    const opt = new Option(item.label, item.id, false, String(item.id) === selectedNotulen);
                    notulenEl.add(opt);
                });
                if ($(notulenEl).data('select2')) {
                    $(notulenEl).trigger('change.select2');
                }
            };

            const refreshAll = () => {
                refreshTemuan();
                refreshNotulen();
            };

            $(semesterEl).on('change', () => {
                selectedTemuan = '';
                selectedNotulen = '';
                refreshAll();
            });

            refreshAll();
        });
    </script>
@endpush
