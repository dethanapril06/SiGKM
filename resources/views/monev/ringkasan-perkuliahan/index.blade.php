@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">Ringkasan Perkuliahan</h4>

        @if (auth()->user()->hasAnyRole(['ketua-gkm', 'anggota-gkm']))
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalSyncRealisasiSiakad" title="Otomatisasi input realisasi dari SIAKAD Cloud">
                    <i class="bx bx-sync me-1"></i> Tarik Realisasi SIAKAD
                </button>
                <a href="{{ route('ringkasan-perkuliahan.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus"></i> Tambah Ringkasan
                </a>
            </div>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('ringkasan-perkuliahan.index') }}">
                <div class="row g-3 align-items-end mb-3">
                    <div class="col-md-4">
                        <label for="semester_id" class="form-label">Semester</label>
                        <select id="semester_id" name="semester_id" class="form-select select2">
                            <option value="">-- Semua Semester --</option>
                            @foreach ($semesters as $semester)
                                <option value="{{ $semester->id }}" @selected($selectedSemester == $semester->id)>
                                    {{ $semester->tahunAkademik->nama ?? '-' }} - {{ ucfirst($semester->nama) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="termin_id" class="form-label">Termin Monev</label>
                        <select id="termin_id" name="termin_id" class="form-select select2">
                            <option value="">-- Semua Termin --</option>
                            @foreach ($termins as $termin)
                                <option value="{{ $termin->id }}" @selected($selectedTermin == $termin->id)>
                                    {{ $termin->nama_termin }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="kesesuaian_materi" class="form-label">Materi Tercapai</label>
                        <select id="kesesuaian_materi" name="kesesuaian_materi" class="form-select">
                            <option value="">-- Semua --</option>
                            <option value="sesuai" @selected($selectedKesesuaian === 'sesuai')>Sesuai</option>
                            <option value="sebagian" @selected($selectedKesesuaian === 'sebagian')>Sebagian</option>
                            <option value="tidak_sesuai" @selected($selectedKesesuaian === 'tidak_sesuai')>Tidak Sesuai</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 border-top">
                    <div class="d-flex align-items-center gap-2">
                        <label for="status" class="form-label mb-0 me-1">Status Verifikasi:</label>
                        <select id="status" name="status" class="form-select form-select-sm" style="width: auto;">
                            <option value="">-- Semua Status --</option>
                            <option value="draft" @selected($selectedStatus === 'draft')>Draft</option>
                            <option value="diajukan" @selected($selectedStatus === 'diajukan')>Diajukan</option>
                            <option value="diverifikasi" @selected($selectedStatus === 'diverifikasi')>Diverifikasi</option>
                            <option value="ditolak" @selected($selectedStatus === 'ditolak')>Ditolak</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bx bx-filter-alt me-1"></i> Terapkan Filter
                        </button>
                        @if ($selectedSemester || $selectedTermin || $selectedKesesuaian || $selectedStatus)
                            <a href="{{ route('ringkasan-perkuliahan.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bx bx-reset me-1"></i> Reset Filter
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <h5 class="card-header">Data Ringkasan Perkuliahan</h5>

        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Jadwal Monev</th>
                        <th>Perkuliahan</th>
                        <th>Jml Pertemuan</th>
                        <th>Kontrak Kuliah</th>
                        <th>Materi Tercapai</th>
                        <th>Pembuat</th>
                        <th>Keterangan (Temuan/Masalah)</th>
                        <th width="150">Aksi</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse ($ringkasanPerkuliahan as $item)
                        <tr>
                            <td>{{ $ringkasanPerkuliahan->firstItem() + $loop->index }}</td>
                            <td>
                                <strong>{{ $item->jadwalMonev->termin->nama_termin ?? '-' }}</strong>
                                <br>
                                <small>
                                    {{ $item->jadwalMonev->semester->tahunAkademik->nama ?? '-' }}
                                    -
                                    {{ ucfirst($item->jadwalMonev->semester->nama ?? '-') }}
                                </small>
                            </td>
                            <td style="max-width: 320px; white-space: normal;">
                                <strong>
                                    {{ $item->perkuliahan->mataKuliah->kode_mk ?? '-' }}
                                    -
                                    {{ $item->perkuliahan->mataKuliah->nama_mk ?? '-' }}
                                </strong>
                                <br>
                                <small>
                                    Kelas {{ $item->perkuliahan->kelas->nama_kelas ?? '-' }}
                                </small>
                            </td>
                            <td>{{ $item->jumlah_pertemuan }}</td>
                            <td>
                                <span class="badge {{ $item->kontrak_kuliah === 'ada' ? 'bg-label-success' : 'bg-label-danger' }}">
                                    {{ $item->kontrak_kuliah === 'ada' ? 'Ada' : 'Tidak Ada' }}
                                </span>
                            </td>
                            <td>
                                @if ($item->kesesuaian_materi === 'sesuai')
                                    <span class="badge bg-label-success">Sesuai</span>
                                @elseif ($item->kesesuaian_materi === 'sebagian')
                                    <span class="badge bg-label-warning">Sebagian</span>
                                @elseif ($item->kesesuaian_materi === 'tidak_sesuai')
                                    <span class="badge bg-label-danger">Tidak Sesuai</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $item->penginput->name ?? '-' }}</td>
                            <td style="max-width: 250px; white-space: normal;">
                                {{ $item->keterangan ?? '-' }}
                            </td>
                            <td>
                                <a href="{{ route('ringkasan-perkuliahan.show', $item) }}" class="btn btn-sm btn-icon btn-info" title="Lihat Detail">
                                    <i class="bx bx-show"></i>
                                </a>
                                @if ($item->canBeEditedBy(auth()->user()))
                                    <a href="{{ route('ringkasan-perkuliahan.edit', $item->id) }}"
                                        class="btn btn-sm btn-icon btn-warning" title="Edit Data">
                                        <i class="bx bx-edit"></i>
                                    </a>

                                    <form action="{{ route('ringkasan-perkuliahan.destroy', $item->id) }}" method="POST"
                                        class="d-inline" data-confirm-form
                                        data-confirm-title="Yakin ingin menghapus ringkasan ini?"
                                        data-confirm-text="Ringkasan yang dihapus tidak dapat dikembalikan."
                                        data-confirm-button-text="Ya, hapus" data-confirm-button-color="#ff3e1d">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-sm btn-icon btn-danger" title="Hapus Data">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">
                                Data ringkasan perkuliahan belum tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">@include('components._pagination', ['paginator' => $ringkasanPerkuliahan])</div>

    <!-- Modal Sinkronisasi Realisasi Monev dari SIAKAD -->
    <div class="modal fade" id="modalSyncRealisasiSiakad" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="form-sync-realisasi-siakad" action="{{ route('ringkasan-perkuliahan.sync-siakad') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-sync me-1 text-primary"></i> Tarik Realisasi Monev dari SIAKAD</h5>
                    <button type="button" id="btn-close-sync-modal" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="sync-form-fields">
                        <div class="alert alert-info py-2 px-3 small mb-3">
                            <i class="bx bx-info-circle me-1"></i>
                            Sistem akan menarik 16 sesi pertemuan per kelas secara bertahap dan menyimpannya langsung ke database agar bebas dari batas waktu server (<em>timeout</em>).
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pilih Jadwal Monev Target <span class="text-danger">*</span></label>
                            <select id="sync-jadwal-monev-id" name="jadwal_monev_id" class="form-select" required>
                                @foreach ($jadwalMonevs ?? [] as $jm)
                                    <option value="{{ $jm->id }}">
                                        {{ $jm->semester->tahunAkademik->nama ?? '-' }} - {{ ucfirst($jm->semester->nama ?? '-') }}
                                        ({{ $jm->termin->nama_termin ?? '-' }})
                                        @if ($jm->status === 'aktif') [Aktif] @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="sync-only-missing" name="only_missing" value="1" checked>
                            <label class="form-check-label small" for="sync-only-missing">
                                Hanya tarik kelas yang belum memiliki ringkasan pada jadwal monev ini (lebih cepat &amp; melanjutkan sisa kelas)
                            </label>
                        </div>
                        <p class="text-muted small mb-0">
                            * Data yang sudah berstatus <em>Diverifikasi</em> tidak akan ditimpa untuk menjaga integritas audit.
                        </p>
                    </div>

                    <div id="sync-progress-panel" class="d-none">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span id="sync-progress-label" class="small fw-semibold">Menyiapkan antrean kelas...</span>
                            <span id="sync-progress-counter" class="small text-muted">0 / 0</span>
                        </div>
                        <div class="progress mb-3" style="height: 18px;">
                            <div id="sync-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;">0%</div>
                        </div>
                        <div id="sync-current-item" class="small text-muted mb-3 text-truncate">
                            Menghubungi SIAKAD Cloud...
                        </div>
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge bg-label-success">Ditambahkan: <strong id="sync-stat-created">0</strong></span>
                            <span class="badge bg-label-info">Diperbarui: <strong id="sync-stat-updated">0</strong></span>
                            <span class="badge bg-label-secondary">Dilewati: <strong id="sync-stat-skipped">0</strong></span>
                            <span class="badge bg-label-warning">Kendala API: <strong id="sync-stat-failed">0</strong></span>
                        </div>
                        <div id="sync-result-alert" class="alert d-none py-2 px-3 small mt-3 mb-0"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btn-cancel-sync-monev" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" id="btn-stop-sync-monev" class="btn btn-outline-danger d-none">
                        <i class="bx bx-stop-circle me-1"></i> Hentikan &amp; Lihat Hasil
                    </button>
                    <button type="button" id="btn-retry-failed-monev" class="btn btn-warning d-none">
                        <i class="bx bx-refresh me-1"></i> Coba Ulang Kelas Gagal
                    </button>
                    <button type="button" id="btn-reload-sync-monev" class="btn btn-success d-none" onclick="window.location.reload();">
                        <i class="bx bx-check me-1"></i> Muat Ulang Tabel
                    </button>
                    <button type="submit" id="btn-submit-sync-monev" class="btn btn-primary">
                        <i class="bx bx-cloud-download me-1"></i> Mulai Tarik Realisasi
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('form-sync-realisasi-siakad');
    if (!form) return;

    const syncUrl = form.getAttribute('action');
    const csrfToken = form.querySelector('input[name="_token"]')?.value || '';
    const selectJadwal = document.getElementById('sync-jadwal-monev-id');
    const checkOnlyMissing = document.getElementById('sync-only-missing');
    const formFields = document.getElementById('sync-form-fields');
    const progressPanel = document.getElementById('sync-progress-panel');
    const progressLabel = document.getElementById('sync-progress-label');
    const progressCounter = document.getElementById('sync-progress-counter');
    const progressBar = document.getElementById('sync-progress-bar');
    const currentItemEl = document.getElementById('sync-current-item');
    const resultAlert = document.getElementById('sync-result-alert');

    const statCreated = document.getElementById('sync-stat-created');
    const statUpdated = document.getElementById('sync-stat-updated');
    const statSkipped = document.getElementById('sync-stat-skipped');
    const statFailed = document.getElementById('sync-stat-failed');

    const btnClose = document.getElementById('btn-close-sync-modal');
    const btnCancel = document.getElementById('btn-cancel-sync-monev');
    const btnStop = document.getElementById('btn-stop-sync-monev');
    const btnRetry = document.getElementById('btn-retry-failed-monev');
    const btnReload = document.getElementById('btn-reload-sync-monev');
    const btnSubmit = document.getElementById('btn-submit-sync-monev');

    let isRunning = false;
    let stopRequested = false;
    let failedQueue = [];
    let stats = { created: 0, updated: 0, skipped: 0, failed: 0 };

    function renderStats() {
        statCreated.textContent = stats.created;
        statUpdated.textContent = stats.updated;
        statSkipped.textContent = stats.skipped;
        statFailed.textContent = stats.failed;
    }

    function showAlert(type, html) {
        resultAlert.className = `alert alert-${type} py-2 px-3 small mt-3 mb-0`;
        resultAlert.innerHTML = html;
    }

    async function postJson(payload) {
        const res = await fetch(syncUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        });
        const data = await res.json().catch(() => null);
        if (!res.ok || !data) {
            throw new Error(data?.message || `HTTP ${res.status}`);
        }
        return data;
    }

    async function processQueue(jadwalMonevId, queue) {
        isRunning = true;
        stopRequested = false;
        failedQueue = [];

        btnSubmit.classList.add('d-none');
        btnCancel.classList.add('d-none');
        btnRetry.classList.add('d-none');
        btnReload.classList.add('d-none');
        btnClose.classList.add('d-none');
        btnStop.classList.remove('d-none');
        btnStop.disabled = false;
        resultAlert.classList.add('d-none');

        progressBar.classList.add('progress-bar-striped', 'progress-bar-animated');

        const total = queue.length;
        for (let i = 0; i < total; i++) {
            if (stopRequested) {
                break;
            }

            const item = queue[i];
            const pctBefore = Math.round((i / total) * 100);
            progressLabel.textContent = 'Menarik realisasi dari SIAKAD Cloud...';
            progressCounter.textContent = `${i + 1} / ${total}`;
            progressBar.style.width = `${pctBefore}%`;
            progressBar.textContent = `${pctBefore}%`;
            currentItemEl.textContent = `Sedang memproses: ${item.label}`;

            const stepStartedAt = Date.now();
            try {
                const step = await postJson({
                    jadwal_monev_id: jadwalMonevId,
                    perkuliahan_id: item.id
                });

                if (step.outcome === 'created') {
                    stats.created++;
                } else if (step.outcome === 'updated') {
                    stats.updated++;
                } else if (step.outcome === 'failed') {
                    stats.failed++;
                    failedQueue.push(item);
                } else {
                    stats.skipped++;
                }
            } catch (err) {
                stats.failed++;
                failedQueue.push(item);
            }
            const stepElapsed = Date.now() - stepStartedAt;

            renderStats();

            const doneCount = i + 1;
            const pctAfter = Math.round((doneCount / total) * 100);
            progressBar.style.width = `${pctAfter}%`;
            progressBar.textContent = `${pctAfter}%`;

            if (i + 1 < total && !stopRequested && stepElapsed > 3000) {
                await new Promise(resolve => setTimeout(resolve, 350));
            }
        }

        isRunning = false;
        progressBar.classList.remove('progress-bar-animated');
        btnStop.classList.add('d-none');
        btnClose.classList.remove('d-none');
        btnReload.classList.remove('d-none');

        if (stopRequested) {
            progressLabel.textContent = 'Sinkronisasi dihentikan pengguna.';
            currentItemEl.textContent = 'Seluruh kelas yang sudah diproses telah tersimpan di database.';
            showAlert('warning', 'Proses dihentikan. Klik <strong>Muat Ulang Tabel</strong> untuk melihat hasil yang sudah tersimpan.');
            return;
        }

        progressLabel.textContent = 'Sinkronisasi selesai!';
        if (failedQueue.length > 0) {
            btnRetry.classList.remove('d-none');
            btnRetry.innerHTML = `<i class="bx bx-refresh me-1"></i> Coba Ulang (${failedQueue.length} Kelas)`;
            currentItemEl.textContent = `${failedQueue.length} kelas mengalami kendala koneksi/timeout pada server SIAKAD.`;
            showAlert(
                'warning',
                `Selesai dengan <strong>${stats.created + stats.updated}</strong> kelas tersimpan dan <strong>${failedQueue.length}</strong> kelas terkendala koneksi API SIAKAD. Anda dapat klik <strong>Coba Ulang</strong> atau langsung <strong>Muat Ulang Tabel</strong>.`
            );
        } else {
            currentItemEl.textContent = 'Seluruh antrean kelas berhasil diproses.';
            showAlert('success', 'Sinkronisasi berhasil! Memuat ulang halaman...');
            setTimeout(() => window.location.reload(), 1000);
        }
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (isRunning) return;

        const jadwalMonevId = selectJadwal.value;
        if (!jadwalMonevId) return;

        stats = { created: 0, updated: 0, skipped: 0, failed: 0 };
        renderStats();

        formFields.classList.add('d-none');
        progressPanel.classList.remove('d-none');
        btnSubmit.disabled = true;
        progressLabel.textContent = 'Mengambil daftar kelas perkuliahan aktif...';
        currentItemEl.textContent = 'Mohon tunggu...';

        try {
            const initData = await postJson({
                jadwal_monev_id: jadwalMonevId,
                mode: 'init',
                only_missing: checkOnlyMissing.checked
            });

            const queue = initData.items || [];
            if (queue.length === 0) {
                btnSubmit.classList.add('d-none');
                btnCancel.classList.add('d-none');
                btnReload.classList.remove('d-none');
                progressBar.style.width = '100%';
                progressBar.textContent = '100%';
                progressBar.classList.remove('progress-bar-animated');
                progressLabel.textContent = 'Tidak ada kelas tersisa untuk ditarik.';
                progressCounter.textContent = `${initData.already_synced || 0} / ${initData.total_active || 0}`;
                currentItemEl.textContent = 'Semua kelas aktif pada jadwal monev ini sudah memiliki ringkasan.';
                showAlert('info', 'Seluruh kelas aktif sudah memiliki data ringkasan. Hilangkan centang <em>Hanya tarik kelas yang belum memiliki ringkasan</em> bila ingin memperbarui ulang.');
                return;
            }

            await processQueue(jadwalMonevId, queue);
        } catch (err) {
            btnSubmit.disabled = false;
            formFields.classList.remove('d-none');
            showAlert('danger', 'Gagal memulai sinkronisasi: ' + err.message);
        }
    });

    btnStop.addEventListener('click', function () {
        if (!isRunning) return;
        stopRequested = true;
        btnStop.disabled = true;
        btnStop.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Menghentikan...';
    });

    btnRetry.addEventListener('click', async function () {
        if (isRunning || failedQueue.length === 0) return;
        const retryItems = [...failedQueue];
        stats.failed = 0;
        renderStats();
        await processQueue(selectJadwal.value, retryItems);
    });
});
</script>
@endpush
