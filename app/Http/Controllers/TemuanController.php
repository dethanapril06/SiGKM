<?php

namespace App\Http\Controllers;

use App\Helpers\CodeGenerator;
use App\Models\EvaluasiIndikator;
use App\Models\Temuan;
use App\Models\TingkatRisiko;
use App\Support\WorkflowStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

use App\Models\Semester;
use App\Models\StandarMutu;
use App\Models\SasaranStrategis;

class TemuanController extends Controller
{
    public function index(): View
    {
        return $this->indexFakultas(request());
    }

    public function indexFakultas(Request $request): View
    {
        $selectedSemester = $request->query('semester_id');
        $selectedStatus = $request->query('status');

        $semesters = Semester::with('tahunAkademik')->orderByDesc('tanggal_mulai')->get();

        $temuanEvaluasi = Temuan::with([
            'evaluasiIndikator.semester.tahunAkademik',
            'evaluasiIndikator.evaluatable',
            'risikoTemuans.tingkatRisiko',
            'pembuat',
        ])
            ->whereHas('evaluasiIndikator', function ($query) use ($selectedSemester) {
                $query->where('evaluatable_type', 'indikator_mutu')
                    ->whereIn('status_capaian', ['dalam_proses', 'belum_tercapai'])
                    ->when($selectedSemester, fn ($q) => $q->where('semester_id', $selectedSemester));
            })
            ->when($selectedStatus, function ($query) use ($selectedStatus) {
                $query->where('status', $selectedStatus);
            })
            ->when(auth()->user()->hasRole('anggota-gkm'), function ($query) {
                $query->where('created_by', auth()->id());
            })
            ->when(auth()->user()->hasRole('koordinator-prodi'), function ($query) {
                $query->whereIn('status', [WorkflowStatus::TERBUKA, WorkflowStatus::DITUTUP]);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('monev.temuan.index', [
            'temuanEvaluasi' => $temuanEvaluasi,
            'judul' => 'Temuan Evaluasi — Fakultas',
            'activeRoute' => 'temuan-evaluasi.fakultas',
            'semesters' => $semesters,
            'selectedSemester' => $selectedSemester,
            'selectedStatus' => $selectedStatus,
        ]);
    }

    public function indexProdi(Request $request): View
    {
        $user = auth()->user();
        $selectedSemester = $request->query('semester_id');
        $selectedStatus = $request->query('status');

        $semesters = Semester::with('tahunAkademik')->orderByDesc('tanggal_mulai')->get();

        $temuanEvaluasi = Temuan::with([
            'evaluasiIndikator.semester.tahunAkademik',
            'evaluasiIndikator.evaluatable',
            'risikoTemuans.tingkatRisiko',
            'pembuat',
        ])
            ->whereHas('evaluasiIndikator', function ($query) use ($selectedSemester) {
                $query->where('evaluatable_type', 'ikks')
                    ->when($selectedSemester, fn ($q) => $q->where('semester_id', $selectedSemester));
            })
            ->when($selectedStatus, function ($query) use ($selectedStatus) {
                $query->where('status', $selectedStatus);
            })
            ->when($user->hasRole('anggota-gkm'), function ($query) use ($user) {
                $query->where('created_by', $user->id);
            })
            ->when($user->hasRole('koordinator-prodi'), function ($query) {
                $query->whereIn('status', [WorkflowStatus::TERBUKA, WorkflowStatus::DITUTUP]);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('monev.temuan.index', [
            'temuanEvaluasi' => $temuanEvaluasi,
            'judul' => 'Temuan Evaluasi — Program Studi',
            'activeRoute' => 'temuan-evaluasi.prodi',
            'semesters' => $semesters,
            'selectedSemester' => $selectedSemester,
            'selectedStatus' => $selectedStatus,
        ]);
    }

    public function show(Temuan $temuan): View
    {
        $temuan->load([
            'evaluasiIndikator.semester.tahunAkademik',
            'evaluasiIndikator.evaluatable',
            'risikoTemuans.tingkatRisiko',
            'pembuat',
            'rencanaTindakLanjuts.buktiTindakLanjuts.pengunggah',
        ]);

        $user = auth()->user();
        $isPublished = $temuan->status !== WorkflowStatus::DRAFT;
        $visible = $user->hasRole('ketua-gkm')
            || ($user->hasRole('koordinator-prodi') && $isPublished)
            || ($user->hasRole('anggota-gkm') && $temuan->created_by === $user->id);
        abort_unless($visible, 403);

        return view('monev.temuan.show', compact('temuan'));
    }

    public function create(): View
    {
        if (! auth()->user()->hasAnyRole(['ketua-gkm', 'anggota-gkm'])) {
            abort(403, 'Hanya Ketua GKM dan Anggota GKM yang dapat membuat temuan.');
        }

        return view('monev.temuan.create', $this->formData() + [
            'kodeTemuan' => CodeGenerator::kodeTemuan(),
            'selectedScope' => request()->query('scope', 'fakultas'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! auth()->user()->hasAnyRole(['ketua-gkm', 'anggota-gkm'])) {
            abort(403, 'Hanya Ketua GKM dan Anggota GKM yang dapat membuat temuan.');
        }

        $validated = $this->validatedData($request);
        $risikoData = $this->validatedRisikoData($request);

        DB::transaction(function () use ($validated, $risikoData) {
            $temuan = Temuan::create($validated + [
                'status' => WorkflowStatus::DRAFT,
                'created_by' => auth()->id(),
            ]);

            if (! empty($risikoData['tingkat_risiko_id']) && ! empty($risikoData['deskripsi_risiko'])) {
                $temuan->risikoTemuans()->create($risikoData);
            }
        });

        $evaluasi = EvaluasiIndikator::find($validated['evaluasi_indikator_id']);
        $redirectRoute = ($evaluasi?->evaluatable_type === 'ikks')
            ? 'temuan-evaluasi.prodi'
            : 'temuan-evaluasi.fakultas';

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Temuan evaluasi berhasil dibuat.');
    }

    public function edit(Temuan $temuan): View
    {
        if (! auth()->user()->hasAnyRole(['ketua-gkm', 'anggota-gkm'])) {
            abort(403, 'Hanya Ketua GKM dan Anggota GKM yang dapat mengubah temuan.');
        }

        $user = auth()->user();
        if ($user->hasRole('anggota-gkm') && $temuan->created_by !== $user->id) {
            abort(403, 'Anda hanya dapat mengubah temuan yang Anda buat.');
        }

        $temuan->load(['risikoTemuans.tingkatRisiko']);

        return view('monev.temuan.edit', array_merge(
            ['temuan' => $temuan],
            $this->formData($temuan)
        ));
    }

    public function update(Request $request, Temuan $temuan): RedirectResponse
    {
        if (! auth()->user()->hasAnyRole(['ketua-gkm', 'anggota-gkm'])) {
            abort(403, 'Hanya Ketua GKM dan Anggota GKM yang dapat mengubah temuan.');
        }

        $user = auth()->user();
        if ($user->hasRole('anggota-gkm') && $temuan->created_by !== $user->id) {
            abort(403, 'Anda hanya dapat mengubah temuan yang Anda buat.');
        }

        $validated = $this->validatedData($request, $temuan);
        $risikoData = $this->validatedRisikoData($request);

        DB::transaction(function () use ($temuan, $validated, $risikoData) {
            $temuan->update($validated);

            if (! empty($risikoData['tingkat_risiko_id']) && ! empty($risikoData['deskripsi_risiko'])) {
                $temuan->risikoTemuans()->updateOrCreate(
                    ['temuan_id' => $temuan->id],
                    $risikoData
                );
            } else {
                $temuan->risikoTemuans()->delete();
            }
        });

        $evaluasi = EvaluasiIndikator::find($validated['evaluasi_indikator_id']);
        $redirectRoute = ($evaluasi?->evaluatable_type === 'ikks')
            ? 'temuan-evaluasi.prodi'
            : 'temuan-evaluasi.fakultas';

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Temuan evaluasi berhasil diperbarui.');
    }

    public function destroy(Temuan $temuan): RedirectResponse
    {
        if (! auth()->user()->hasAnyRole(['ketua-gkm', 'anggota-gkm'])) {
            abort(403, 'Hanya Ketua GKM dan Anggota GKM yang dapat menghapus temuan.');
        }

        $user = auth()->user();
        if ($user->hasRole('anggota-gkm') && $temuan->created_by !== $user->id) {
            abort(403, 'Anda hanya dapat menghapus temuan yang Anda buat.');
        }

        if ($temuan->rencanaTindakLanjuts()->exists()) {
            return back()->with('error', 'Temuan tidak dapat dihapus karena sudah digunakan pada RTL.');
        }

        $redirectRoute = ($temuan->evaluasiIndikator?->evaluatable_type === 'ikks')
            ? 'temuan-evaluasi.prodi'
            : 'temuan-evaluasi.fakultas';

        $temuan->delete();

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Temuan evaluasi berhasil dihapus.');
    }

    private function formData(?Temuan $temuan = null): array
    {
        $evaluasiIndikator = EvaluasiIndikator::with([
            'semester.tahunAkademik',
            'evaluatable',
        ])
            ->whereIn('status_capaian', ['dalam_proses', 'belum_tercapai'])
            ->when($temuan, function ($query) use ($temuan) {
                $query->orWhere('id', $temuan->evaluasi_indikator_id);
            })
            ->latest()
            ->get();

        $evaluasiFakultas = EvaluasiIndikator::with([
            'semester.tahunAkademik',
            'evaluatable.standarMutu',
        ])
            ->where('evaluatable_type', 'indikator_mutu')
            ->whereIn('status_capaian', ['dalam_proses', 'belum_tercapai'])
            ->when($temuan && $temuan->evaluasiIndikator?->evaluatable_type === 'indikator_mutu', function ($query) use ($temuan) {
                $query->orWhere('id', $temuan->evaluasi_indikator_id);
            })
            ->latest()
            ->get();

        $tingkatRisiko = TingkatRisiko::orderBy('nama_tingkat')->get();

        $standarMutus = StandarMutu::active()
            ->with(['indikatorMutus' => function ($q) use ($temuan) {
                $q->where('is_active', true)
                    ->with(['evaluasiIndikators' => function ($eq) use ($temuan) {
                        $eq->whereIn('status_capaian', ['dalam_proses', 'belum_tercapai'])
                            ->when($temuan, fn ($q2) => $q2->orWhere('id', $temuan->evaluasi_indikator_id))
                            ->with('semester.tahunAkademik');
                    }]);
            }])
            ->get();

        $sasaranStrategises = SasaranStrategis::where('is_active', true)
            ->with(['indikatorKinerjaUtamas' => function ($q) use ($temuan) {
                $q->where('is_active', true)
                    ->with(['indikatorKinerjaKegiatans' => function ($iq) use ($temuan) {
                        $iq->where('is_active', true)
                            ->with(['indikatorKinerjaKegiatanSatuan' => function ($isq) use ($temuan) {
                                $isq->where('is_active', true)
                                    ->with(['evaluasiIndikators' => function ($eq) use ($temuan) {
                                        $eq->whereIn('status_capaian', ['dalam_proses', 'belum_tercapai'])
                                            ->when($temuan, fn ($q2) => $q2->orWhere('id', $temuan->evaluasi_indikator_id))
                                            ->with('semester.tahunAkademik');
                                    }]);
                            }]);
                    }]);
            }])
            ->get();

        return compact('evaluasiIndikator', 'evaluasiFakultas', 'tingkatRisiko', 'standarMutus', 'sasaranStrategises');
    }

    private function validatedData(Request $request, ?Temuan $temuan = null): array
    {
        return $request->validate([
            'evaluasi_indikator_id' => ['required', 'exists:evaluasi_indikators,id'],
            'kode_temuan' => [
                'required',
                'string',
                'max:50',
                Rule::unique('temuans', 'kode_temuan')->ignore($temuan),
            ],
            'pernyataan' => ['required', 'string'],
            'rencana_awal' => ['nullable', 'string'],
            'target_selesai' => ['nullable', 'string', 'max:255'],
            'target_capaian' => ['nullable', 'string'],
        ], [
            'evaluasi_indikator_id.required' => 'Evaluasi indikator wajib dipilih.',
            'kode_temuan.required' => 'Kode temuan wajib diisi.',
            'kode_temuan.unique' => 'Kode temuan sudah digunakan.',
            'pernyataan.required' => 'Pernyataan temuan wajib diisi.',
        ]);
    }

    private function validatedRisikoData(Request $request): array
    {
        return $request->validate([
            'tingkat_risiko_id' => ['nullable', 'required_with:deskripsi_risiko', 'exists:tingkat_risikos,id'],
            'deskripsi_risiko' => ['nullable', 'required_with:tingkat_risiko_id', 'string'],
            'dampak_risiko' => ['nullable', 'string'],
        ], [
            'tingkat_risiko_id.required_with' => 'Tingkat risiko wajib dipilih jika deskripsi risiko diisi.',
            'deskripsi_risiko.required_with' => 'Deskripsi risiko wajib diisi jika tingkat risiko dipilih.',
        ]);
    }
}
