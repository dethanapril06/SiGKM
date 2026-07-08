<?php

namespace App\Http\Controllers;

use App\Models\JadwalMonev;
use App\Models\Perkuliahan;
use App\Models\RingkasanPerkuliahan;
use App\Services\WorkflowNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RingkasanPerkuliahanController extends Controller
{
    public function __construct(private WorkflowNotificationService $notifications) {}

    public function index(Request $request): View
    {
        $user = auth()->user();

        $ringkasanPerkuliahan = RingkasanPerkuliahan::with([
            'jadwalMonev.semester.tahunAkademik',
            'jadwalMonev.termin',
            'perkuliahan.mataKuliah',
            'perkuliahan.kelas',
            'perkuliahan.semester.tahunAkademik',
            'perkuliahan.pengajars.dosen',
            'penginput',
            'verifikator',
        ])
            ->when($user->hasRole('anggota-gkm'), function ($query) use ($user) {
                $query->where('input_by', $user->id);
            })
            ->when($user->hasRole('ketua-gkm'), function ($query) {
                $query->whereIn('status', ['diajukan', 'diverifikasi', 'ditolak']);
            })
            ->when($user->hasRole('koordinator-prodi'), function ($query) {
                $query->whereIn('status', ['diajukan', 'diverifikasi', 'ditolak']);
            })
            ->when($user->hasRole('dosen') && $user->dosen_id, function ($query) use ($user) {
                $query->whereIn('status', ['diajukan', 'diverifikasi', 'ditolak'])
                    ->whereHas('perkuliahan.pengajars', function ($q) use ($user) {
                        $q->where('dosen_id', $user->dosen_id);
                    });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('monev.ringkasan-perkuliahan.index', compact('ringkasanPerkuliahan'));
    }

    public function create(): View
    {
        $jadwalMonev = JadwalMonev::with(['semester.tahunAkademik', 'termin'])
            ->where('status', 'aktif')
            ->latest()
            ->get();

        $perkuliahan = Perkuliahan::with([
            'semester.tahunAkademik',
            'mataKuliah',
            'kelas',
            'pengajars.dosen',
        ])
            ->where('status', 'aktif')
            ->latest()
            ->get();

        return view('monev.ringkasan-perkuliahan.create', compact(
            'jadwalMonev',
            'perkuliahan'
        ));
    }

    public function show(RingkasanPerkuliahan $ringkasanPerkuliahan): View
    {
        $ringkasanPerkuliahan->load([
            'jadwalMonev.semester.tahunAkademik',
            'jadwalMonev.termin',
            'perkuliahan.mataKuliah',
            'perkuliahan.kelas',
            'perkuliahan.pengajars.dosen',
            'penginput',
            'verifikator',
        ]);

        $user = auth()->user();
        $isOwner = $ringkasanPerkuliahan->input_by === $user->id;
        $isPublished = $ringkasanPerkuliahan->status !== 'draft';
        $visible = $isOwner
            || ($user->hasAnyRole(['ketua-gkm', 'koordinator-prodi']) && $isPublished)
            || ($user->hasRole('dosen') && $isPublished && $ringkasanPerkuliahan->perkuliahan?->pengajars?->contains('dosen_id', $user->dosen_id));
        abort_unless($visible, 403);

        return view('monev.ringkasan-perkuliahan.show', compact('ringkasanPerkuliahan'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jadwal_monev_id' => ['required', 'exists:jadwal_monevs,id'],
            'perkuliahan_id' => ['required', 'exists:perkuliahans,id'],
            'jumlah_pertemuan' => ['required', 'integer', 'min:0', 'max:30'],
            'kesesuaian_materi' => ['required', 'in:sesuai,sebagian,tidak_sesuai'],
            'metode_pembelajaran' => ['nullable', 'string'],
            'keterangan' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,diajukan'],
        ], [
            'jadwal_monev_id.required' => 'Jadwal monev wajib dipilih.',
            'perkuliahan_id.required' => 'Perkuliahan wajib dipilih.',
            'jumlah_pertemuan.required' => 'Jumlah pertemuan wajib diisi.',
            'kesesuaian_materi.required' => 'Kesesuaian materi wajib dipilih.',
        ]);

        $isDuplicate = RingkasanPerkuliahan::query()
            ->where('jadwal_monev_id', $validated['jadwal_monev_id'])
            ->where('perkuliahan_id', $validated['perkuliahan_id'])
            ->exists();

        if ($isDuplicate) {
            throw ValidationException::withMessages([
                'perkuliahan_id' => 'Ringkasan untuk jadwal monev dan perkuliahan tersebut sudah tersedia.',
            ]);
        }

        $validated['input_by'] = auth()->id();

        $ringkasan = RingkasanPerkuliahan::create($validated);

        if ($ringkasan->status === 'diajukan') {
            $this->notifyRingkasanSubmitted($ringkasan);
        }

        return redirect()
            ->route('ringkasan-perkuliahan.index')
            ->with('success', 'Ringkasan perkuliahan berhasil ditambahkan.');
    }

    public function edit(RingkasanPerkuliahan $ringkasanPerkuliahan): View
    {
        if (! $ringkasanPerkuliahan->canBeEditedBy(auth()->user())) {
            abort(403, 'Ringkasan ini tidak dapat diedit.');
        }

        $jadwalMonev = JadwalMonev::with(['semester.tahunAkademik', 'termin'])
            ->latest()
            ->get();

        $perkuliahan = Perkuliahan::with([
            'semester.tahunAkademik',
            'mataKuliah',
            'kelas',
            'pengajars.dosen',
        ])
            ->latest()
            ->get();

        return view('monev.ringkasan-perkuliahan.edit', compact(
            'ringkasanPerkuliahan',
            'jadwalMonev',
            'perkuliahan'
        ));
    }

    public function update(Request $request, RingkasanPerkuliahan $ringkasanPerkuliahan): RedirectResponse
    {
        if (! $ringkasanPerkuliahan->canBeEditedBy(auth()->user())) {
            abort(403, 'Ringkasan ini tidak dapat diedit.');
        }

        $validated = $request->validate([
            'jadwal_monev_id' => ['required', 'exists:jadwal_monevs,id'],
            'perkuliahan_id' => ['required', 'exists:perkuliahans,id'],
            'jumlah_pertemuan' => ['required', 'integer', 'min:0', 'max:30'],
            'kesesuaian_materi' => ['required', 'in:sesuai,sebagian,tidak_sesuai'],
            'metode_pembelajaran' => ['nullable', 'string'],
            'keterangan' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,diajukan'],
        ], [
            'kesesuaian_materi.required' => 'Kesesuaian materi wajib dipilih.',
        ]);

        $isDuplicate = RingkasanPerkuliahan::query()
            ->where('jadwal_monev_id', $validated['jadwal_monev_id'])
            ->where('perkuliahan_id', $validated['perkuliahan_id'])
            ->whereKeyNot($ringkasanPerkuliahan->id)
            ->exists();

        if ($isDuplicate) {
            throw ValidationException::withMessages([
                'perkuliahan_id' => 'Ringkasan untuk jadwal monev dan perkuliahan tersebut sudah tersedia.',
            ]);
        }

        $validated['catatan_verifikasi'] = null;
        $validated['verified_by'] = null;
        $validated['verified_at'] = null;

        $ringkasanPerkuliahan->update($validated);

        if ($ringkasanPerkuliahan->status === 'diajukan') {
            $this->notifyRingkasanSubmitted($ringkasanPerkuliahan);
        }

        return redirect()
            ->route('ringkasan-perkuliahan.index')
            ->with('success', 'Ringkasan perkuliahan berhasil diperbarui.');
    }

    public function destroy(RingkasanPerkuliahan $ringkasanPerkuliahan): RedirectResponse
    {
        if (! $ringkasanPerkuliahan->canBeEditedBy(auth()->user())) {
            abort(403, 'Ringkasan ini tidak dapat dihapus.');
        }

        $ringkasanPerkuliahan->delete();

        return redirect()
            ->route('ringkasan-perkuliahan.index')
            ->with('success', 'Ringkasan perkuliahan berhasil dihapus.');
    }

    public function submit(RingkasanPerkuliahan $ringkasanPerkuliahan): RedirectResponse
    {
        if (! $ringkasanPerkuliahan->canBeEditedBy(auth()->user())) {
            abort(403, 'Ringkasan ini tidak dapat diajukan.');
        }

        $ringkasanPerkuliahan->update([
            'status' => 'diajukan',
            'catatan_verifikasi' => null,
            'verified_by' => null,
            'verified_at' => null,
        ]);

        $this->notifyRingkasanSubmitted($ringkasanPerkuliahan);

        return back()->with('success', 'Ringkasan perkuliahan berhasil diajukan ke Ketua GKM.');
    }

    public function verify(RingkasanPerkuliahan $ringkasanPerkuliahan): RedirectResponse
    {
        if (! auth()->user()->hasRole('ketua-gkm')) {
            abort(403, 'Hanya Ketua GKM yang dapat memverifikasi ringkasan.');
        }

        if (! $ringkasanPerkuliahan->isDiajukan()) {
            return back()->with('error', 'Hanya ringkasan berstatus diajukan yang dapat diverifikasi.');
        }

        $ringkasanPerkuliahan->update([
            'status' => 'diverifikasi',
            'catatan_verifikasi' => null,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $this->notifications->sendToUser(
            $ringkasanPerkuliahan->penginput,
            'Ringkasan Diverifikasi',
            'Ringkasan perkuliahan Anda telah diverifikasi oleh Ketua GKM.',
            route('ringkasan-perkuliahan.show', $ringkasanPerkuliahan),
            'bx-check-circle',
            'success',
        );

        return back()->with('success', 'Ringkasan perkuliahan berhasil diverifikasi.');
    }

    public function reject(Request $request, RingkasanPerkuliahan $ringkasanPerkuliahan): RedirectResponse
    {
        if (! auth()->user()->hasRole('ketua-gkm')) {
            abort(403, 'Hanya Ketua GKM yang dapat menolak ringkasan.');
        }

        if (! $ringkasanPerkuliahan->isDiajukan()) {
            return back()->with('error', 'Hanya ringkasan berstatus diajukan yang dapat ditolak.');
        }

        $validated = $request->validate([
            'catatan_verifikasi' => ['required', 'string'],
        ], [
            'catatan_verifikasi.required' => 'Catatan penolakan wajib diisi.',
        ]);

        $ringkasanPerkuliahan->update([
            'status' => 'ditolak',
            'catatan_verifikasi' => $validated['catatan_verifikasi'],
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $this->notifications->sendToUser(
            $ringkasanPerkuliahan->penginput,
            'Ringkasan Ditolak',
            'Ringkasan perkuliahan perlu diperbaiki: '.$validated['catatan_verifikasi'],
            route('ringkasan-perkuliahan.show', $ringkasanPerkuliahan),
            'bx-error-circle',
            'danger',
        );

        return back()->with('success', 'Ringkasan perkuliahan berhasil ditolak.');
    }

    private function notifyRingkasanSubmitted(RingkasanPerkuliahan $ringkasan): void
    {
        $this->notifications->sendToRole(
            'ketua-gkm',
            'Ringkasan Menunggu Verifikasi',
            'Ringkasan perkuliahan baru telah diajukan oleh '.($ringkasan->penginput?->name ?? 'Anggota GKM').'.',
            route('verifikasi.index'),
            'bx-book-content',
            'warning',
        );
    }
}
