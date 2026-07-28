<div class="card mb-4 no-print">
    <div class="card-body">
        <form method="GET" action="{{ url()->current() }}">
            <div class="row align-items-end">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Tahun Akademik</label>
                    <select name="tahun_akademik_id" class="form-select select2">
                        <option value="">-- Semua Tahun Akademik --</option>

                        @foreach ($tahunAkademik as $item)
                            <option value="{{ $item->id }}"
                                {{ request('tahun_akademik_id') == $item->id ? 'selected' : '' }}>
                                {{ $item->nama_tahun_akademik }}
                                {{ $item->is_active ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Semester</label>
                    <select name="semester_id" class="form-select select2">
                        <option value="">-- Semua Semester --</option>

                        @foreach ($semester as $item)
                            <option value="{{ $item->id }}"
                                {{ request('semester_id') == $item->id ? 'selected' : '' }}>
                                {{ $item->tahunAkademik->nama_tahun_akademik ?? '-' }}
                                - {{ $item->nama_semester }}
                                {{ $item->is_active ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label d-none d-md-block">&nbsp;</label>

                    <div class="d-flex gap-2 w-100">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bx bx-filter"></i> Filter
                        </button>

                        <a href="{{ url()->current() }}" class="btn btn-secondary flex-fill">
                            <i class="bx bx-reset"></i> Reset
                        </a>

                        <button type="button" onclick="window.print()" class="btn btn-success flex-fill">
                            <i class="bx bx-printer"></i> Cetak
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
