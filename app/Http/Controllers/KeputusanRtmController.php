<?php

namespace App\Http\Controllers;

use App\Models\IndikatorKinerjaKegiatanSatuan;
use App\Models\IndikatorMutu;
use App\Models\KeputusanRtm;
use App\Models\NotulenRtm;
use App\Models\Semester;
use App\Models\Temuan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class KeputusanRtmController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('keputusan-rtm.fakultas');
    }

    public function indexFakultas(Request $request): View
    {
        $selectedSemester = $request->query('semester_id');
        $selectedStatus = $request->query('status');

        $semesters = Semester::with('tahunAkademik')->orderByDesc('tanggal_mulai')->get();

        $keputusanRtm = KeputusanRtm::with([
            'notulenRtm.jadwalRtm.semester.tahunAkademik',
            'temuan.evaluasiIndikator.semester.tahunAkademik',
            'temuan.evaluasiIndikator.evaluatable',
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

        return view('rtm.keputusan.index', [
            'keputusanRtm' => $keputusanRtm,
            'judul' => 'Keputusan RTM — Fakultas',
            'activeRoute' => 'keputusan-rtm.fakultas',
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

        $keputusanRtm = KeputusanRtm::with([
            'notulenRtm.jadwalRtm.semester.tahunAkademik',
            'temuan.evaluasiIndikator.semester.tahunAkademik',
            'temuan.evaluasiIndikator.evaluatable',
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

        return view('rtm.keputusan.index', [
            'keputusanRtm' => $keputusanRtm,
            'judul' => 'Keputusan RTM — Program Studi',
            'activeRoute' => 'keputusan-rtm.prodi',
            'semesters' => $semesters,
            'selectedSemester' => $selectedSemester,
            'selectedStatus' => $selectedStatus,
        ]);
    }

    public function create(): View
    {
        return view('rtm.keputusan.create', $this->formData());
    }

    public function show(KeputusanRtm $keputusanRtm): View
    {
        $keputusanRtm->load([
            'notulenRtm.jadwalRtm.semester.tahunAkademik',
            'temuan.evaluasiIndikator.semester.tahunAkademik',
            'temuan.evaluasiIndikator.evaluatable' => function ($morphTo) {
                $morphTo->morphWith([
                    IndikatorMutu::class => ['standarMutu'],
                    IndikatorKinerjaKegiatanSatuan::class => ['indikatorKinerjaKegiatan.indikatorKinerjaUtama.sasaranStrategis'],
                ]);
            },
        ]);

        return view('rtm.keputusan.show', compact('keputusanRtm'));
    }

    public function store(Request $request): RedirectResponse
    {
        KeputusanRtm::create($this->validated($request));

        return redirect()->route('keputusan-rtm.fakultas')->with('success', 'Keputusan RTM berhasil dibuat.');
    }

    public function edit(KeputusanRtm $keputusanRtm): View
    {
        return view('rtm.keputusan.edit', array_merge(
            ['keputusanRtm' => $keputusanRtm],
            $this->formData($keputusanRtm)
        ));
    }

    public function update(Request $request, KeputusanRtm $keputusanRtm): RedirectResponse
    {
        $keputusanRtm->update($this->validated($request, $keputusanRtm));

        return redirect()->route('keputusan-rtm.fakultas')->with('success', 'Keputusan RTM berhasil diperbarui.');
    }

    public function destroy(KeputusanRtm $keputusanRtm): RedirectResponse
    {
        $keputusanRtm->delete();

        return back()->with('success', 'Keputusan RTM berhasil dihapus.');
    }

    private function validated(Request $request, ?KeputusanRtm $keputusanRtm = null): array
    {
        $data = $request->validate([
            'notulen_rtm_id' => ['required', Rule::exists('notulen_rtms', 'id')->where('status', 'diverifikasi')],
            'temuan_id' => ['required', 'exists:temuans,id'],
            'uraian_keputusan' => ['required', 'string'],
            'strategi' => ['nullable', 'string'],
        ]);

        $eligible = $this->eligibleTemuanQuery((int) $data['notulen_rtm_id'], $keputusanRtm)
            ->whereKey($data['temuan_id'])
            ->exists();

        if (! $eligible) {
            throw ValidationException::withMessages([
                'temuan_id' => 'Temuan tidak valid atau sudah diputuskan pada RTM ini.',
            ]);
        }

        return $data;
    }

    private function formData(?KeputusanRtm $keputusanRtm = null): array
    {
        $notulenRtm = NotulenRtm::with('jadwalRtm.semester.tahunAkademik')
            ->where('status', 'diverifikasi')
            ->latest('verified_at')
            ->get();

        $temuanByNotulen = $notulenRtm->mapWithKeys(fn ($notulen) => [
            $notulen->id => $this->eligibleTemuanQuery($notulen->id, $keputusanRtm)
                ->with([
                    'evaluasiIndikator.semester.tahunAkademik',
                    'evaluasiIndikator.evaluatable' => function ($morphTo) {
                        $morphTo->morphWith([
                            IndikatorMutu::class => ['standarMutu'],
                            IndikatorKinerjaKegiatanSatuan::class => ['indikatorKinerjaKegiatan.indikatorKinerjaUtama.sasaranStrategis'],
                        ]);
                    },
                ])
                ->get()
                ->map(function ($temuan) {
                    $kodeStandar = $temuan->kode_standar ?? '-';
                    $kodeIndikator = $temuan->kode_indikator ?? '-';

                    return [
                        'id' => $temuan->id,
                        'label' => $temuan->kode_temuan." | [{$kodeStandar} • {$kodeIndikator}] | ".str($temuan->pernyataan)->limit(90),
                    ];
                })->values(),
        ]);

        return compact('notulenRtm', 'temuanByNotulen');
    }

    private function eligibleTemuanQuery(int $notulenId, ?KeputusanRtm $current = null)
    {
        return Temuan::query()
            ->where(function ($query) use ($notulenId, $current) {
                $query->whereDoesntHave('keputusanRtms', fn ($q) => $q->where('notulen_rtm_id', $notulenId));
                if ($current && (int) $current->notulen_rtm_id === $notulenId) {
                    $query->orWhere('id', $current->temuan_id);
                }
            });
    }
}
