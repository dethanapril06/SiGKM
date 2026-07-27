@php($current = $notulenRtm ?? null)
<div class="mb-3">
    <label class="form-label fw-semibold">Jadwal RTM</label>
    <select name="jadwal_rtm_id" class="form-select @error('jadwal_rtm_id') is-invalid @enderror" required>
        <option value="">-- Pilih Jadwal --</option>
        @foreach($jadwalRtm as $item)
            <option value="{{ $item->id }}" @selected(old('jadwal_rtm_id', $current?->jadwal_rtm_id) == $item->id)>
                {{ $item->judul }} — {{ $item->semester?->label }}
            </option>
        @endforeach
    </select>
    @error('jadwal_rtm_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label fw-semibold">Isi Notulen</label>
    <textarea name="isi_notulen" id="isi_notulen" class="form-control @error('isi_notulen') is-invalid @enderror">{{ old('isi_notulen', $current?->isi_notulen) }}</textarea>
    @error('isi_notulen')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>

<hr class="my-4">

<h6 class="fw-bold mb-3">Lampiran Notulen RTM (Opsional)</h6>
<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label fw-semibold">1. Lampiran Undangan Rapat (PDF)</label>
        <input type="file" name="file_undangan" accept="application/pdf" class="form-control @error('file_undangan') is-invalid @enderror">
        @if($current?->file_undangan)
            <small class="d-block mt-1">
                File saat ini: <a href="{{ asset('storage/'.$current->file_undangan) }}" target="_blank" class="fw-bold text-danger"><i class="bx bxs-file-pdf"></i> Lihat File Undangan (PDF)</a>
            </small>
        @endif
        @error('file_undangan')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-muted d-block mt-1">Khusus berkas PDF, max 5MB. Ditampilkan pada halaman 1.</small>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label fw-semibold">2. Lembar Absensi Rapat (PDF)</label>
        <input type="file" name="file_absensi" accept="application/pdf" class="form-control @error('file_absensi') is-invalid @enderror">
        @if($current?->file_absensi)
            <small class="d-block mt-1">
                File saat ini: <a href="{{ asset('storage/'.$current->file_absensi) }}" target="_blank" class="fw-bold text-danger"><i class="bx bxs-file-pdf"></i> Lihat File Absensi (PDF)</a>
            </small>
        @endif
        @error('file_absensi')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-muted d-block mt-1">Khusus berkas PDF, max 5MB. Ditampilkan pada halaman 3.</small>
    </div>

    <div class="col-md-4 mb-3">
        <label class="form-label fw-semibold">3. Dokumentasi Rapat (Foto)</label>
        <input type="file" name="file_dokumentasi[]" multiple accept="image/jpeg,image/png,image/webp" class="form-control @error('file_dokumentasi') is-invalid @enderror @error('file_dokumentasi.*') is-invalid @enderror">
        @if($current && count($current->dokumentasi_list) > 0)
            <div class="mt-2">
                <small class="d-block text-muted mb-1">Foto saat ini ({{ count($current->dokumentasi_list) }} foto):</small>
                <div class="d-flex gap-2 flex-wrap">
                    @foreach($current->dokumentasi_list as $dokPath)
                        <a href="{{ asset('storage/'.$dokPath) }}" target="_blank" class="d-inline-block border rounded p-1">
                            <img src="{{ asset('storage/'.$dokPath) }}" style="height: 45px; width: 45px; object-fit: cover;" class="rounded">
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
        @error('file_dokumentasi')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @error('file_dokumentasi.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        <small class="text-muted d-block mt-1">Format foto (JPG, PNG, WEBP), maksimal 3 foto, max 2MB/foto. Ditampilkan di halaman terakhir.</small>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof tinymce !== 'undefined') {
                tinymce.init({
                    selector: '#isi_notulen',
                    height: 420,
                    menubar: false,
                    plugins: 'advlist autolink lists link table code wordcount',
                    toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist | table link | code',
                    advlist_number_types: 'upper-alpha,lower-alpha,upper-roman,lower-roman,default',
                    content_style: 'body { font-family: "Public Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; line-height: 1.6; }',
                    placeholder: 'Tuliskan atau tempel hasil notulen RTM di sini...'
                });
            }
        });
    </script>
@endpush
