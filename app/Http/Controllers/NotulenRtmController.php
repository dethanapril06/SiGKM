<?php

namespace App\Http\Controllers;

use App\Models\JadwalRtm;
use App\Models\NotulenRtm;
use App\Services\WorkflowNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NotulenRtmController extends Controller
{
    public function __construct(private WorkflowNotificationService $notifications) {}

    public function index(): View
    {
        $this->authorize('viewAny', NotulenRtm::class);
        $notulenRtm = NotulenRtm::with([
            'jadwalRtm.semester.tahunAkademik', 'penginput', 'verifikator',
        ])->latest()->paginate(10)->withQueryString();

        return view('rtm.notulen.index', compact('notulenRtm'));
    }

    public function create(): View
    {
        $this->authorize('create', NotulenRtm::class);

        return view('rtm.notulen.create', ['jadwalRtm' => $this->availableSchedules()]);
    }

    public function show(NotulenRtm $notulenRtm): View
    {
        $this->authorize('view', $notulenRtm);
        $notulenRtm->load([
            'jadwalRtm.semester.tahunAkademik',
            'penginput',
            'verifikator',
            'keputusanRtms.rencanaTindakLanjut.temuan',
        ]);

        return view('rtm.notulen.show', compact('notulenRtm'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', NotulenRtm::class);
        $data = $this->validated($request);
        $data['input_by'] = auth()->id();
        $data['status'] = 'draft';
        NotulenRtm::create($data);

        return redirect()->route('notulen-rtm.index')->with('success', 'Notulen RTM berhasil disimpan sebagai draft.');
    }

    public function edit(NotulenRtm $notulenRtm): View
    {
        $this->authorize('update', $notulenRtm);

        return view('rtm.notulen.edit', [
            'notulenRtm' => $notulenRtm,
            'jadwalRtm' => $this->availableSchedules($notulenRtm),
        ]);
    }

    public function update(Request $request, NotulenRtm $notulenRtm): RedirectResponse
    {
        $this->authorize('update', $notulenRtm);
        $notulenRtm->update(array_merge($this->validated($request, $notulenRtm), [
            'status' => 'draft',
            'verified_by' => null,
            'verified_at' => null,
            'catatan_verifikasi' => null,
        ]));

        return redirect()->route('notulen-rtm.index')->with('success', 'Notulen RTM berhasil diperbarui.');
    }

    public function destroy(NotulenRtm $notulenRtm): RedirectResponse
    {
        $this->authorize('delete', $notulenRtm);
        $notulenRtm->delete();

        return back()->with('success', 'Notulen RTM berhasil dihapus.');
    }

    public function submit(NotulenRtm $notulenRtm): RedirectResponse
    {
        $this->authorize('submit', $notulenRtm);
        $notulenRtm->update([
            'status' => 'diajukan',
            'verified_by' => null,
            'verified_at' => null,
            'catatan_verifikasi' => null,
        ]);

        $this->notifications->sendToRole(
            'ketua-gkm',
            'Notulen RTM Menunggu Verifikasi',
            'Notulen untuk '.$notulenRtm->jadwalRtm->judul.' telah diajukan oleh '.($notulenRtm->penginput?->name ?? 'Anggota GKM').'.',
            route('verifikasi.index'),
            'bx-notepad',
            'warning',
        );

        return back()->with('success', 'Notulen RTM berhasil diajukan kepada Ketua GKM.');
    }

    public function verify(NotulenRtm $notulenRtm): RedirectResponse
    {
        $this->authorize('verify', $notulenRtm);
        $notulenRtm->update([
            'status' => 'diverifikasi',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'catatan_verifikasi' => null,
        ]);

        $this->notifications->sendToUser(
            $notulenRtm->penginput,
            'Notulen RTM Diverifikasi',
            'Notulen '.$notulenRtm->jadwalRtm->judul.' telah diverifikasi oleh Ketua GKM.',
            route('notulen-rtm.show', $notulenRtm),
            'bx-check-circle',
            'success',
        );

        return back()->with('success', 'Notulen RTM berhasil diverifikasi.');
    }

    public function reject(Request $request, NotulenRtm $notulenRtm): RedirectResponse
    {
        $this->authorize('reject', $notulenRtm);
        $data = $request->validate(['catatan_verifikasi' => ['required', 'string']]);
        $notulenRtm->update([
            'status' => 'ditolak',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'catatan_verifikasi' => $data['catatan_verifikasi'],
        ]);

        $this->notifications->sendToUser(
            $notulenRtm->penginput,
            'Notulen RTM Ditolak',
            'Notulen perlu diperbaiki: '.$data['catatan_verifikasi'],
            route('notulen-rtm.show', $notulenRtm),
            'bx-error-circle',
            'danger',
        );

        return back()->with('success', 'Notulen RTM ditolak dan dikembalikan kepada Anggota GKM.');
    }

    private function validated(Request $request, ?NotulenRtm $notulenRtm = null): array
    {
        return $request->validate([
            'jadwal_rtm_id' => [
                'required',
                'exists:jadwal_rtms,id',
                Rule::unique('notulen_rtms', 'jadwal_rtm_id')->ignore($notulenRtm),
            ],
            'isi_notulen' => ['required', 'string'],
        ]);
    }

    private function availableSchedules(?NotulenRtm $notulenRtm = null)
    {
        return JadwalRtm::with('semester.tahunAkademik')
            ->where(function ($query) use ($notulenRtm) {
                $query->whereDoesntHave('notulenRtm');
                if ($notulenRtm) {
                    $query->orWhereKey($notulenRtm->jadwal_rtm_id);
                }
            })
            ->orderByDesc('tanggal')
            ->get();
    }
}
