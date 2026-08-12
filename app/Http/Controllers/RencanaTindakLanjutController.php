<?php

namespace App\Http\Controllers;

use App\Models\EvaluasiIndikator;
use App\Models\RencanaTindakLanjut;
use App\Models\Temuan;
use App\Support\WorkflowStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

use App\Models\IndikatorKinerjaKegiatanSatuan;
use App\Models\IndikatorMutu;
use App\Models\Semester;

class RencanaTindakLanjutController extends Controller
{
    public function index(): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('rtl.fakultas');
    }

    public function indexFakultas(Request $request): View
    {
        $selectedSemester = $request->query('semester_id');
        $selectedStatus = $request->query('status');

        $semesters = Semester::with('tahunAkademik')->orderByDesc('tanggal_mulai')->get();

        $rtl = RencanaTindakLanjut::with([
            'temuan.evaluasiIndikator.semester.tahunAkademik',
            'temuan.evaluasiIndikator.evaluatable',
            'buktiTindakLanjuts.pengunggah',
        ])
            ->whereHas('temuan.evaluasiIndikator', function ($query) use ($selectedSemester) {
                $query->where('evaluatable_type', 'indikator_mutu')
                    ->when($selectedSemester, fn ($q) => $q->where('semester_id', $selectedSemester));
            })
            ->when($selectedStatus, function ($query) use ($selectedStatus) {
                $query->whereHas('temuan', fn ($q) => $q->where('status', $selectedStatus));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('monev.rtl.index', [
            'rtl' => $rtl,
            'judul' => 'RTL Fakultas',
            'activeRoute' => 'rtl.fakultas',
            'semesters' => $semesters,
            'selectedSemester' => $selectedSemester,
            'selectedStatus' => $selectedStatus,
        ]);
    }

    public function indexProdi(Request $request): View
    {
        $selectedSemester = $request->query('semester_id');
        $selectedStatus = $request->query('status');

        $semesters = Semester::with('tahunAkademik')->orderByDesc('tanggal_mulai')->get();

        $rtl = RencanaTindakLanjut::with([
            'temuan.evaluasiIndikator.semester.tahunAkademik',
            'temuan.evaluasiIndikator.evaluatable',
            'buktiTindakLanjuts.pengunggah',
        ])
            ->whereHas('temuan.evaluasiIndikator', function ($query) use ($selectedSemester) {
                $query->where('evaluatable_type', 'ikks')
                    ->when($selectedSemester, fn ($q) => $q->where('semester_id', $selectedSemester));
            })
            ->when($selectedStatus, function ($query) use ($selectedStatus) {
                $query->whereHas('temuan', fn ($q) => $q->where('status', $selectedStatus));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('monev.rtl.index', [
            'rtl' => $rtl,
            'judul' => 'RTL Program Studi',
            'activeRoute' => 'rtl.prodi',
            'semesters' => $semesters,
            'selectedSemester' => $selectedSemester,
            'selectedStatus' => $selectedStatus,
        ]);
    }

    public function create(): View
    {
        if (! auth()->user()->hasAnyRole(['ketua-gkm', 'anggota-gkm'])) {
            abort(403, 'Hanya Ketua GKM dan Anggota GKM yang dapat mencatat Realisasi RTL.');
        }

        $scope = request()->query('scope', 'fakultas');

        return view('monev.rtl.create', array_merge(
            ['selectedScope' => $scope],
            $this->formData(null, $scope)
        ));
    }

    public function show(RencanaTindakLanjut $rtl): View
    {
        $rtl->load([
            'temuan.evaluasiIndikator.semester.tahunAkademik',
            'temuan.evaluasiIndikator.evaluatable' => function ($morphTo) {
                $morphTo->morphWith([
                    IndikatorMutu::class => ['standarMutu'],
                    IndikatorKinerjaKegiatanSatuan::class => ['indikatorKinerjaKegiatan.indikatorKinerjaUtama.sasaranStrategis'],
                ]);
            },
            'buktiTindakLanjuts.pengunggah',
            'keputusanRtms',
        ]);

        return view('monev.rtl.show', compact('rtl'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (! auth()->user()->hasAnyRole(['ketua-gkm', 'anggota-gkm'])) {
            abort(403, 'Hanya Ketua GKM dan Anggota GKM yang dapat mencatat Realisasi RTL.');
        }

        $validated = $this->validatedData($request);

        DB::transaction(function () use ($request, $validated) {
            $rtl = RencanaTindakLanjut::create($validated);
            $this->storeBuktiFiles($request, $rtl);

            $temuan = $rtl->temuan;
            if ($temuan) {
                $temuan->update([
                    'status' => WorkflowStatus::DITUTUP,
                ]);

                if ($temuan->evaluasi_indikator_id) {
                    EvaluasiIndikator::where('id', $temuan->evaluasi_indikator_id)
                        ->update(['status_capaian' => 'tercapai']);
                }
            }
        });

        $temuan = Temuan::with('evaluasiIndikator')->find($validated['temuan_id']);
        $redirectRoute = ($temuan?->evaluasiIndikator?->evaluatable_type === 'ikks')
            ? 'rtl.prodi'
            : 'rtl.fakultas';

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Realisasi Rencana Tindak Lanjut berhasil disimpan.');
    }

    public function edit(RencanaTindakLanjut $rtl): View
    {
        if (! $rtl->canBeEditedBy(auth()->user())) {
            abort(403, 'Realisasi RTL ini tidak dapat diedit.');
        }

        return view('monev.rtl.edit', array_merge(
            ['rtl' => $rtl],
            $this->formData($rtl)
        ));
    }

    public function update(Request $request, RencanaTindakLanjut $rtl): RedirectResponse
    {
        if (! $rtl->canBeEditedBy(auth()->user())) {
            abort(403, 'Realisasi RTL ini tidak dapat diedit.');
        }

        $validated = $this->validatedData($request, $rtl);

        DB::transaction(function () use ($request, $rtl, $validated) {
            $rtl->update($validated);
            $this->storeBuktiFiles($request, $rtl);
        });

        $temuan = Temuan::with('evaluasiIndikator')->find($validated['temuan_id']);
        $redirectRoute = ($temuan?->evaluasiIndikator?->evaluatable_type === 'ikks')
            ? 'rtl.prodi'
            : 'rtl.fakultas';

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Realisasi Rencana Tindak Lanjut berhasil diperbarui.');
    }

    public function destroy(RencanaTindakLanjut $rtl): RedirectResponse
    {
        if (! $rtl->canBeEditedBy(auth()->user())) {
            abort(403, 'Realisasi RTL ini tidak dapat dihapus.');
        }

        if ($rtl->buktiTindakLanjuts()->exists()) {
            foreach ($rtl->buktiTindakLanjuts as $bukti) {
                Storage::disk('public')->delete($bukti->file_path);
            }
        }

        $redirectRoute = ($rtl->temuan?->evaluasiIndikator?->evaluatable_type === 'ikks')
            ? 'rtl.prodi'
            : 'rtl.fakultas';

        DB::transaction(function () use ($rtl) {
            $temuan = $rtl->temuan;
            if ($temuan) {
                $temuan->update([
                    'status' => WorkflowStatus::TERBUKA,
                ]);

                if ($temuan->evaluasi_indikator_id) {
                    EvaluasiIndikator::where('id', $temuan->evaluasi_indikator_id)
                        ->update(['status_capaian' => 'dalam_proses']);
                }
            }

            $rtl->delete();
        });

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Realisasi Rencana Tindak Lanjut berhasil dihapus.');
    }

    private function formData(?RencanaTindakLanjut $rtl = null, ?string $scope = null): array
    {
        $typeFilter = $scope === 'prodi' ? 'ikks' : ($scope === 'fakultas' ? 'indikator_mutu' : null);

        $temuan = Temuan::with([
            'evaluasiIndikator.semester.tahunAkademik',
            'evaluasiIndikator.evaluatable' => function ($morphTo) {
                $morphTo->morphWith([
                    IndikatorMutu::class => ['standarMutu'],
                    IndikatorKinerjaKegiatanSatuan::class => ['indikatorKinerjaKegiatan.indikatorKinerjaUtama.sasaranStrategis'],
                ]);
            },
        ])
            ->whereIn('status', [WorkflowStatus::TERBUKA, WorkflowStatus::DRAFT])
            ->when($typeFilter, function ($query) use ($typeFilter) {
                $query->whereHas('evaluasiIndikator', fn ($q) => $q->where('evaluatable_type', $typeFilter));
            })
            ->when($rtl, function ($query) use ($rtl) {
                $query->where(function ($q) use ($rtl) {
                    $q->whereDoesntHave('rencanaTindakLanjuts')
                        ->orWhere('id', $rtl->temuan_id);
                });
            }, function ($query) {
                $query->whereDoesntHave('rencanaTindakLanjuts');
            })
            ->latest()
            ->get();

        return compact('temuan');
    }

    private function validatedData(Request $request, ?RencanaTindakLanjut $rtl = null): array
    {
        return $request->validate([
            'temuan_id' => [
                'required',
                Rule::exists('temuans', 'id'),
                Rule::unique('rencana_tindak_lanjuts', 'temuan_id')->ignore($rtl),
            ],
            'penanggung_jawab' => ['nullable', 'string', 'max:255'],
            'uraian_realisasi' => ['required', 'string'],
            'waktu_pelaksanaan' => ['required', 'date'],
            'catatan' => ['nullable', 'string'],
            'bukti.*' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:2048'],
            'keterangan_bukti.*' => ['nullable', 'string'],
        ], [
            'temuan_id.required' => 'Temuan wajib dipilih.',
            'temuan_id.unique' => 'Temuan ini sudah memiliki realisasi RTL.',
            'uraian_realisasi.required' => 'Uraian realisasi wajib diisi.',
            'waktu_pelaksanaan.required' => 'Waktu pelaksanaan wajib diisi.',
            'bukti.*.mimes' => 'File bukti harus berupa PDF, DOC, DOCX, JPG, JPEG, atau PNG.',
            'bukti.*.max' => 'Ukuran file bukti maksimal 2 MB.',
        ]);
    }

    private function storeBuktiFiles(Request $request, RencanaTindakLanjut $rtl): void
    {
        if (! $request->hasFile('bukti')) {
            return;
        }

        foreach ($request->file('bukti') as $index => $file) {
            if (! $file) {
                continue;
            }

            $rtl->buktiTindakLanjuts()->create([
                'file_path' => $file->store('bukti-tindak-lanjut', 'public'),
                'keterangan' => $request->input("keterangan_bukti.{$index}"),
                'uploaded_by' => auth()->id(),
            ]);
        }
    }
}
