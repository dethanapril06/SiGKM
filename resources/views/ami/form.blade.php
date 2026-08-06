@php
    $current = $ami ?? null;
    $fileFields = [
        'file_ami'           => 'File AMI',
        'file_tindak_lanjut' => 'File Tindak Lanjut',
        'file_dokumentasi'   => 'File Dokumentasi',
        'file_absensi'       => 'File Absensi',
    ];
@endphp

<div class="mb-3">
    <label class="form-label">Tahun Akademik <span class="text-danger">*</span></label>
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
    <label class="form-label">Tanggal Pelaksanaan <span class="text-danger">*</span></label>
    <input type="date" name="tanggal_pelaksanaan"
        class="form-control @error('tanggal_pelaksanaan') is-invalid @enderror"
        value="{{ old('tanggal_pelaksanaan', $current?->tanggal_pelaksanaan?->format('Y-m-d')) }}" required>
    @error('tanggal_pelaksanaan')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<hr class="my-4">

<h6 class="fw-bold mb-3">Berkas AMI</h6>
<p class="text-muted small mb-3">Format: PDF, Word, Excel, JPG, PNG. Maksimal 5 MB per file.</p>

<div class="row">
    @foreach($fileFields as $field => $label)
        <div class="col-md-6 mb-3">
            <label class="form-label">{{ $label }}</label>
            <input type="file" name="{{ $field }}"
                class="form-control @error($field) is-invalid @enderror"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
            @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
            @if($current && $current->$field)
                <small class="text-muted d-block mt-1">
                    File saat ini:
                    <a href="{{ asset('storage/' . $current->$field) }}" target="_blank" class="text-primary">
                        <i class="bx bx-file"></i> Lihat file
                    </a>
                    <span class="text-muted">(upload baru akan mengganti file lama)</span>
                </small>
            @endif
        </div>
    @endforeach
</div>
