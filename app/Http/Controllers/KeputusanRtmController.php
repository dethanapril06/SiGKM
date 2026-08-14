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
            'semester.tahunAkademik',
            'notulenRtm.jadwalRtm.semester.tahunAkademik',
            'temuan.evaluasiIndikator.semester.tahunAkademik',
            'temuan.evaluasiIndikator.evaluatable',
        ])
            ->whereHas('temuan.evaluasiIndikator', function ($query) {
                $query->where('evaluatable_type', 'indikator_mutu');
            })
            ->when($selectedSemester, function ($query) use ($selectedSemester) {
                $query->where(function ($q) use ($selectedSemester) {
                    $q->where('semester_id', $selectedSemester)
                        ->orWhereHas('temuan.evaluasiIndikator', fn ($sub) => $sub->where('semester_id', $selectedSemester));
                });
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
            'semester.tahunAkademik',
            'notulenRtm.jadwalRtm.semester.tahunAkademik',
            'temuan.evaluasiIndikator.semester.tahunAkademik',
            'temuan.evaluasiIndikator.evaluatable',
        ])
            ->whereHas('temuan.evaluasiIndikator', function ($query) {
                $query->where('evaluatable_type', 'ikks');
            })
            ->when($selectedSemester, function ($query) use ($selectedSemester) {
                $query->where(function ($q) use ($selectedSemester) {
                    $q->where('semester_id', $selectedSemester)
                        ->orWhereHas('temuan.evaluasiIndikator', fn ($sub) => $sub->where('semester_id', $selectedSemester));
                });
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
            'semester.tahunAkademik',
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
        $validated = $this->validated($request);
        KeputusanRtm::create($validated);

        $temuan = Temuan::with('evaluasiIndikator')->find($validated['temuan_id']);
        $redirectRoute = ($temuan?->evaluasiIndikator?->evaluatable_type === 'ikks')
            ? 'keputusan-rtm.prodi'
            : 'keputusan-rtm.fakultas';

        return redirect()->route($redirectRoute)->with('success', 'Keputusan RTM berhasil dibuat.');
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
        $validated = $this->validated($request, $keputusanRtm);
        $keputusanRtm->update($validated);

        $temuan = Temuan::with('evaluasiIndikator')->find($validated['temuan_id']);
        $redirectRoute = ($temuan?->evaluasiIndikator?->evaluatable_type === 'ikks')
            ? 'keputusan-rtm.prodi'
            : 'keputusan-rtm.fakultas';

        return redirect()->route($redirectRoute)->with('success', 'Keputusan RTM berhasil diperbarui.');
    }

    public function destroy(KeputusanRtm $keputusanRtm): RedirectResponse
    {
        $redirectRoute = ($keputusanRtm->temuan?->evaluasiIndikator?->evaluatable_type === 'ikks')
            ? 'keputusan-rtm.prodi'
            : 'keputusan-rtm.fakultas';

        $keputusanRtm->delete();

        return redirect()->route($redirectRoute)->with('success', 'Keputusan RTM berhasil dihapus.');
    }

    private function validated(Request $request, ?KeputusanRtm $keputusanRtm = null): array
    {
        $data = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'notulen_rtm_id' => [
                'nullable',
                Rule::exists('notulen_rtms', 'id')->where('status', 'diverifikasi'),
            ],
            'temuan_id' => ['required', 'exists:temuans,id'],
            'uraian_keputusan' => ['required', 'string'],
            'strategi' => ['nullable', 'string'],
        ], [
            'semester_id.required' => 'Semester wajib dipilih.',
            'semester_id.exists' => 'Semester yang dipilih tidak valid.',
            'temuan_id.required' => 'Temuan wajib dipilih.',
            'temuan_id.exists' => 'Temuan yang dipilih tidak valid.',
            'uraian_keputusan.required' => 'Uraian keputusan wajib diisi.',
        ]);

        $eligible = $this->eligibleTemuanQuery((int) $data['semester_id'], $keputusanRtm)
            ->whereKey($data['temuan_id'])
            ->exists();

        if (! $eligible) {
            throw ValidationException::withMessages([
                'temuan_id' => 'Temuan tidak valid atau sudah diputuskan pada semester ini.',
            ]);
        }

        return $data;
    }

    private function formData(?KeputusanRtm $keputusanRtm = null): array
    {
        $semesters = Semester::with('tahunAkademik')->orderByDesc('tanggal_mulai')->get();

        $notulenRtm = NotulenRtm::with('jadwalRtm.semester.tahunAkademik')
            ->where('status', 'diverifikasi')
            ->latest('verified_at')
            ->get();

        $notulenBySemester = $semesters->mapWithKeys(function ($semester) use ($notulenRtm) {
            $filtered = $notulenRtm->filter(function ($item) use ($semester) {
                return (int) $item->jadwalRtm?->semester_id === (int) $semester->id;
            })->map(function ($item) {
                return [
                    'id' => $item->id,
                    'label' => ($item->jadwalRtm?->judul ?? 'RTM').' ('.($item->jadwalRtm?->tanggal?->format('d/m/Y') ?? '-').')',
                ];
            })->values();

            return [$semester->id => $filtered];
        });

        $temuanBySemester = $semesters->mapWithKeys(function ($semester) use ($keputusanRtm) {
            $temuans = $this->eligibleTemuanQuery($semester->id, $keputusanRtm)
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
                    $semesterLabel = $temuan->evaluasiIndikator?->semester?->label ?? '';

                    return [
                        'id' => $temuan->id,
                        'label' => $temuan->kode_temuan." | [{$kodeStandar} • {$kodeIndikator}] | ".str($temuan->pernyataan)->limit(80).($semesterLabel ? " ({$semesterLabel})" : ''),
                    ];
                })->values();

            return [$semester->id => $temuans];
        });

        return compact('semesters', 'notulenRtm', 'notulenBySemester', 'temuanBySemester');
    }

    private function eligibleTemuanQuery(int $semesterId, ?KeputusanRtm $current = null)
    {
        $targetSemester = Semester::find($semesterId);

        return Temuan::query()
            ->where(function ($query) use ($targetSemester) {
                if ($targetSemester?->tanggal_mulai) {
                    $query->whereHas('evaluasiIndikator.semester', function ($q) use ($targetSemester) {
                        $q->where('tanggal_mulai', '<=', $targetSemester->tanggal_mulai);
                    })->orWhereDoesntHave('evaluasiIndikator');
                }
            })
            ->where(function ($query) use ($semesterId, $current) {
                $query->whereDoesntHave('keputusanRtms', fn ($q) => $q->where('semester_id', $semesterId));
                if ($current && (int) $current->semester_id === $semesterId) {
                    $query->orWhere('id', $current->temuan_id);
                }
            });
    }
}
