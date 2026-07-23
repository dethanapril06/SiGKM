@php($current = $ami ?? null)
<div class="mb-3">
    <label class="form-label">Tahun Akademik</label>
    <select name="tahun_akademik_id" class="form-select @error('tahun_akademik_id') is-invalid @enderror" required>
        <option value="">-- Pilih Tahun Akademik --</option>
        @foreach($tahunAkademik as $item)
            <option value="{{ $item->id }}" @selected(old('tahun_akademik_id', $current?->tahun_akademik_id) == $item->id)>
                {{ $item->nama }} {{ $item->is_active ? '(Aktif)' : '' }}
            </option>
        @endforeach
    </select>
    @error('tahun_akademik_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label">Tanggal Pelaksanaan</label>
    <input type="date" name="tanggal_pelaksanaan" class="form-control @error('tanggal_pelaksanaan') is-invalid @enderror" value="{{ old('tanggal_pelaksanaan', $current?->tanggal_pelaksanaan?->format('Y-m-d')) }}" required>
    @error('tanggal_pelaksanaan')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label">Temuan AMI</label>
    <textarea name="temuan" rows="5" class="form-control @error('temuan') is-invalid @enderror" required>{{ old('temuan', $current?->temuan) }}</textarea>
    @error('temuan')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label">Rekomendasi</label>
    <textarea name="rekomendasi" rows="4" class="form-control @error('rekomendasi') is-invalid @enderror" required>{{ old('rekomendasi', $current?->rekomendasi) }}</textarea>
    @error('rekomendasi')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label class="form-label">Tindak Lanjut</label>
    <textarea name="tindak_lanjut" rows="4" class="form-control">{{ old('tindak_lanjut', $current?->tindak_lanjut) }}</textarea>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Target Selesai</label>
        <input type="text" name="target_selesai" class="form-control @error('target_selesai') is-invalid @enderror" value="{{ old('target_selesai', $current?->target_selesai) }}" placeholder="Contoh: Semester depan, Akhir Tahun, dsb.">
        @error('target_selesai')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            @foreach(['draft'=>'Draft','aktif'=>'Aktif','selesai'=>'Selesai'] as $value=>$label)
                <option value="{{ $value }}" @selected(old('status', $current?->status ?? 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

<hr class="my-4">

<h6 class="fw-bold mb-3">Upload Bukti AMI (Opsional)</h6>
<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">Nama Bukti</label>
        <input type="text" name="nama_dokumen" class="form-control @error('nama_dokumen') is-invalid @enderror" value="{{ old('nama_dokumen') }}" placeholder="Contoh: Laporan AMI, SK Tim, dsb.">
        @error('nama_dokumen')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">File Bukti</label>
        <input type="file" name="document_file" class="form-control @error('document_file') is-invalid @enderror">
        @error('document_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-muted">Maksimal 5 MB (PDF, Word, Excel, JPG, PNG)</small>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Link Google Drive</label>
        <input type="url" name="link_url" class="form-control @error('link_url') is-invalid @enderror" value="{{ old('link_url') }}" placeholder="https://drive.google.com/...">
        @error('link_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
