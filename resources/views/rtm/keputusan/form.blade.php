@php($current = $keputusanRtm ?? null)
<div class="alert alert-info">
    Pilih RTM, lalu pilih Temuan yang akan diberikan Keputusan.</div>
<div class="mb-3">
    <label class="form-label">Notulen RTM Terverifikasi</label>
    <select id="notulen_rtm_id" name="notulen_rtm_id" class="form-select select2 @error('notulen_rtm_id') is-invalid @enderror"
        required>
        <option value="">-- Pilih RTM --</option>
        @foreach ($notulenRtm as $item)
            <option value="{{ $item->id }}" @selected(old('notulen_rtm_id', $current?->notulen_rtm_id) == $item->id)>{{ $item->jadwalRtm?->judul }} —
                {{ $item->jadwalRtm?->semester?->label }}</option>
        @endforeach
    </select>
    @error('notulen_rtm_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label class="form-label">Temuan yang Ditinjau</label>
    <select id="temuan_id" name="temuan_id"
        class="form-select select2 @error('temuan_id') is-invalid @enderror" required
        data-selected="{{ old('temuan_id', $current?->temuan_id) }}">
        <option value="">-- Pilih RTM terlebih dahulu --</option>
    </select>
    @error('temuan_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3"><label class="form-label">Uraian Keputusan</label>
    <textarea name="uraian_keputusan" rows="5" class="form-control @error('uraian_keputusan') is-invalid @enderror"
        required>{{ old('uraian_keputusan', $current?->uraian_keputusan) }}</textarea>
    @error('uraian_keputusan')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3"><label class="form-label">Strategi</label>
    <textarea name="strategi" rows="4" class="form-control @error('strategi') is-invalid @enderror"
        placeholder="Strategi untuk menjalankan keputusan RTM">{{ old('strategi', $current?->strategi) }}</textarea>
    @error('strategi')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const temuanByNotulen = @json($temuanByNotulen);
            const notulen = document.getElementById('notulen_rtm_id');
            const temuan = document.getElementById('temuan_id');
            const selected = String(temuan.dataset.selected || '');
            const refresh = () => {
                const options = temuanByNotulen[notulen.value] || [];
                temuan.innerHTML = '<option value="">-- Pilih Temuan --</option>';
                options.forEach(item => {
                    const option = new Option(item.label, item.id, false, String(item.id) === selected);
                    temuan.add(option);
                });
                if (!options.length && notulen.value) temuan.innerHTML =
                    '<option value="">Tidak ada Temuan yang tersedia untuk RTM ini</option>';

                if ($(temuan).data('select2')) {
                    $(temuan).trigger('change.select2');
                }
            };
            $(notulen).on('change', refresh);
            refresh();
        });
    </script>
@endpush
